import random
from datetime import datetime, timedelta

# Configuration
subjects = {
    1: 'OOP', 2: 'IT Fundamentals', 3: 'Java', 
    4: 'Python', 5: 'Information Management', 6: 'DSA'
}
cards_per_day = 20
days = 7
remember_rate = 0.85 # 85% chance to remember

start_date = datetime.now() - timedelta(days=days)
card_id = 1
session_id = 1
log_id = 1

sql_statements = []

for day in range(days):
    current_date = start_date + timedelta(days=day)
    
    for deck_id, deck_name in subjects.items():
        # Insert 20 new cards for this deck today
        new_cards = []
        for c in range(cards_per_day):
            sql_statements.append(f"INSERT INTO flashcards (id, deck_id, front, back, interval_days, next_review_at, review_count, lapses, created_at) VALUES ({card_id}, {deck_id}, 'Front of {deck_name} card {card_id}', 'Back of {deck_name} card {card_id}', 1, '{current_date.strftime('%Y-%m-%d %H:%M:%S')}', 0, 0, '{current_date.strftime('%Y-%m-%d %H:%M:%S')}');")
            new_cards.append(card_id)
            card_id += 1
            
        # Simulate a review session for this deck (reviewing new cards + due old cards would go here)
        # For simplicity in mock generation, we review the new cards immediately
        remembered_count = 0
        logs = []
        
        for cid in new_cards:
            rating = 'remembered' if random.random() < remember_rate else 'forgot'
            prev_int = 1
            new_int = max(1, prev_int * 2) if rating == 'remembered' else 0
            
            if rating == 'remembered':
                remembered_count += 1
                
            response_time = random.randint(1500, 8000)
            logs.append(f"INSERT INTO review_logs (id, session_id, card_id, rating, previous_interval, new_interval, response_time_ms, reviewed_at) VALUES ({log_id}, {session_id}, {cid}, '{rating}', {prev_int}, {new_int}, {response_time}, '{current_date.strftime('%Y-%m-%d %H:%M:%S')}');")
            log_id += 1
            
        # Append Session
        sql_statements.append(f"INSERT INTO review_sessions (id, user_id, deck_id, started_at, ended_at, total_reviewed, remembered_count) VALUES ({session_id}, 1, {deck_id}, '{current_date.strftime('%Y-%m-%d %H:%M:%S')}', '{(current_date + timedelta(minutes=15)).strftime('%Y-%m-%d %H:%M:%S')}', {len(new_cards)}, {remembered_count});")
        session_id += 1
        
        # Append Logs
        sql_statements.extend(logs)

# Write to file
with open('mempulse_mock_data.sql', 'w') as f:
    f.write('\n'.join(sql_statements))

print(f"Generated {card_id - 1} cards and {log_id - 1} review logs.")