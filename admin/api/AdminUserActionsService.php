<?php
/**
 * AdminUserActionsService
 *
 * Server-enforced user management mutations:
 *  - Single & bulk actions: approve, reject, delete (soft-delete), restore, make_admin, make_user
 *  - Direct user creation by an admin
 *  - Prevents self-lockout / self-modification
 *  - Writes every action atomically to admin_audit_log
 */
class AdminUserActionsService
{
    public const ACTIONS = [
        'approve',
        'reject',
        'delete',
        'restore',
        'make_admin',
        'make_user',
    ];

    private const AUDIT_NAMES = [
        'approve'    => 'user_approved',
        'reject'     => 'user_rejected',
        'delete'     => 'user_deleted',
        'restore'    => 'user_restored',
        'make_admin' => 'role_promoted_admin',
        'make_user'  => 'role_demoted_user',
        'create'     => 'user_created_by_admin',
    ];

    /**
     * Allowed actions for a user row (shared between UI and server enforcement).
     */
    public static function allowedActions(string $role, string $status, bool$isDeleted = false): array
    {
        if ($isDeleted || $status === 'deleted') {
            return ['restore'];
        }

        $actions = [];
        switch ($status) {
            case 'pending':
                $actions = ['approve', 'reject', 'delete'];
                break;
            case 'approved':
                $actions = ['reject', 'delete'];
                break;
            case 'rejected':
                $actions = ['approve', 'delete'];
                break;
        }

        if ($status === 'approved') {
            $actions[] = ($role === 'admin') ? 'make_user' : 'make_admin';
        }

        return $actions;
    }

    /**
     * Perform an action on a single user.
     */
    public static function perform(mysqli $conn, int $actorId, int$targetId, string $action, string$ip = ''): array
    {
        if (!in_array($action, self::ACTIONS, true) || $targetId <= 0) {
            return self::fail(400, 'That request is not valid.');
        }
        if ($targetId ===$actorId) {
            return self::fail(403, 'You cannot change or delete your own account.');
        }

        try {
            mysqli_begin_transaction($conn);

            $stmt = mysqli_prepare($conn,
                'SELECT first_name, last_name, email, role, account_status, deleted_at
                   FROM users WHERE id = ? FOR UPDATE'
            );
            if (!$stmt) {
                throw new RuntimeException('prepare failed (select)');
            }
            mysqli_stmt_bind_param($stmt, 'i',$targetId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $first,$last, $email,$role, $status,$deletedAt);
            $found = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if (!$found) {
                mysqli_rollback($conn);
                return self::fail(404, 'That account does not exist.');
            }

            $isDeleted = ($deletedAt !== null);
            $allowed   = self::allowedActions((string)$role, (string) $status,$isDeleted);

            if (!in_array($action,$allowed, true)) {
                mysqli_rollback($conn);
                return self::fail(409, 'This action is not valid for the account\'s current state. Refresh the page.');
            }

            // Prevent deleting or rejecting the last approved admin
            if ($role === 'admin' && in_array($action, ['reject', 'delete', 'make_user'], true)) {
                $admRes = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'admin' AND account_status = 'approved' AND deleted_at IS NULL");
                $admRow = $admRes ? mysqli_fetch_row($admRes) : [0];
                if ($admRes) mysqli_free_result($admRes);
                if ((int) ($admRow[0] ?? 0) <= 1) {
                    mysqli_rollback($conn);
                    return self::fail(409, 'Cannot remove the last active administrator.');
                }
            }

            $prevState = $isDeleted ? 'deleted' : (string) $status;
            $newState  =$prevState;

            if ($action === 'delete') {
                $sql      = 'UPDATE users SET deleted_at = NOW() WHERE id = ?';$newState = 'deleted';
            } elseif ($action === 'restore') {$sql      = 'UPDATE users SET deleted_at = NULL WHERE id = ?';
                $newState = (string)$status;
            } elseif ($action === 'approve' || $action === 'reject') {$newState = $action === 'approve' ? 'approved' : 'rejected';$sql      = "UPDATE users SET account_status = '$newState' WHERE id = ?";
            } elseif ($action === 'make_admin' || $action === 'make_user') {
                $newRole  =$action === 'make_admin' ? 'admin' : 'user';
                $prevState = 'role:' .$role;
                $newState  = 'role:' .$newRole;
                $sql       = "UPDATE users SET role = '$newRole' WHERE id = ?";
            }

            $up = mysqli_prepare($conn,$sql);
            if (!$up) {
                throw new RuntimeException('prepare failed (update)');
            }
            mysqli_stmt_bind_param($up, 'i',$targetId);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);

            $name      = trim($first . ' ' .$last);
            $name      =$name !== '' ? $name :$email;
            $auditName = self::AUDIT_NAMES[$action];
            $ip        = mb_substr($ip, 0, 45);

            $log = mysqli_prepare($conn,
                'INSERT INTO admin_audit_log
                    (admin_id, action, target_user_id, target_email, target_name, previous_status, new_status, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            if (!$log) {
                throw new RuntimeException('prepare failed (audit)');
            }
            mysqli_stmt_bind_param($log, 'isisssss', $actorId,$auditName, $targetId,$email, $name,$prevState, $newState,$ip);
            mysqli_stmt_execute($log);
            mysqli_stmt_close($log);

            mysqli_commit($conn);
        } catch (Throwable $e) {
            @mysqli_rollback($conn);
            error_log('AdminUserActionsService::perform failed: ' . $e->getMessage());
            return self::fail(500, 'Something went wrong and nothing was changed. Please try again.');
        }

        $messages = [
            'approve'    => "Approved {$name}.",
            'reject'     => "Suspended {$name}.",
            'delete'     => "Archived {$name}.",
            'restore'    => "Restored {$name}.",
            'make_admin' => "Promoted {$name} to Admin.",
            'make_user'  => "Changed {$name} to regular User.",
        ];
        return ['ok' => true, 'code' => 200, 'message' => $messages[$action], 'status' =>$newState];
    }

    /**
     * Perform a bulk action on multiple user IDs.
     */
    public static function performBulk(mysqli $conn, int $actorId, array$targetIds, string $action, string$ip = ''): array
    {
        if (!in_array($action, ['approve', 'reject', 'delete', 'restore'], true)) {
            return self::fail(400, 'Invalid bulk action.');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval',$targetIds), fn($id) =>$id > 0 && $id !==$actorId)));
        if (!$ids) {
            return self::fail(400, 'No valid accounts selected (you cannot modify your own account).');
        }

        $succeeded = 0;
        foreach ($ids as$id) {
            $res = self::perform($conn, $actorId,$id, $action,$ip);
            if ($res['ok']) {$succeeded++;
            }
        }

        if ($succeeded === 0) {
            return self::fail(409, 'None of the selected accounts could be updated.');
        }

        $verb = [ 'approve' => 'Approved', 'reject'  => 'Suspended', 'delete'  => 'Archived', 'restore' => 'Restored', ][$action];

        return [
            'ok'      => true,
            'code'    => 200,
            'message' => "{$verb} {$succeeded} " . ($succeeded === 1 ? 'account.' : 'accounts.'),
        ];
    }

    /**
     * Create a new user account directly from the Admin Users page.
     */
    public static function createUser(mysqli $conn, int$actorId, array $data, string$ip = ''): array
    {
        $first    = trim((string) ($data['first_name'] ?? ''));
        $last     = trim((string) ($data['last_name'] ?? ''));
        $email    = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role     = in_array($data['role'] ?? 'user', ['admin', 'user'], true) ? $data['role'] : 'user';$status   = in_array($data['status'] ?? 'approved', ['approved', 'pending'], true) ?$data['status'] : 'approved';

        if ($first === '' || $last === '' || $email === '' || $password === '') {
            return self::fail(400, 'All fields are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::fail(400, 'Please enter a valid email address.');
        }
        if (strlen($password) < 8) {
            return self::fail(400, 'Password must be at least 8 characters long.');
        }

        try {
            mysqli_begin_transaction($conn);

            $chk = mysqli_prepare($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1');
            mysqli_stmt_bind_param($chk, 's',$email);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);
            if (mysqli_stmt_num_rows($chk) > 0) {
                mysqli_stmt_close($chk);
                mysqli_rollback($conn);
                return self::fail(409, 'An account with that email already exists.');
            }
            mysqli_stmt_close($chk);

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = mysqli_prepare($conn,
                'INSERT INTO users (first_name, last_name, email, password_hash, role, account_status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($ins, 'ssssss', $first,$last, $email,$hash, $role,$status);
            mysqli_stmt_execute($ins);
            $newId = (int) mysqli_insert_id($conn);
            mysqli_stmt_close($ins);

            $set = mysqli_prepare($conn, 'INSERT IGNORE INTO user_settings (user_id) VALUES (?)');
            if ($set) {
                mysqli_stmt_bind_param($set, 'i',$newId);
                mysqli_stmt_execute($set);
                mysqli_stmt_close($set);
            }

            $name      = trim($first . ' ' .$last);
            $auditName = self::AUDIT_NAMES['create'];$ip        = mb_substr($ip, 0, 45);$prev      = null;

            $log = mysqli_prepare($conn,
                'INSERT INTO admin_audit_log
                    (admin_id, action, target_user_id, target_email, target_name, previous_status, new_status, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param($log, 'isisssss', $actorId,$auditName, $newId,$email, $name,$prev, $status,$ip);
            mysqli_stmt_execute($log);
            mysqli_stmt_close($log);

            mysqli_commit($conn);
            return ['ok' => true, 'code' => 200, 'message' => "Created account for {$name}."];
        } catch (Throwable $e) {
            @mysqli_rollback($conn);
            error_log('AdminUserActionsService::createUser failed: ' . $e->getMessage());
            return self::fail(500, 'Unable to create the account.');
        }
    }
}