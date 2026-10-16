import os
from pathlib import Path

def main():
    output_filename = input("Enter the filename for the output (e.g., combined.txt): ").strip()
    if not output_filename:
        print("Output filename cannot be empty. Exiting.")
        return
    
    print("\nType the files or relative paths you want to find and include (separated by spaces).")
    print("Example: app/views/dashboard.php admin/views/index.php")
    print("(Entering just a name like 'dashboard.php' will prompt you if duplicates exist.)")
    
    inputs = input("> ").strip().split()
    
    if not inputs:
        print("No files provided. Exiting.")
        return

    root_dir = Path('.')

    with open(output_filename, 'w', encoding='utf-8') as outfile:
        for query in inputs:
            print(f"\nSearching for '{query}'...")
            
            # Check if user passed a direct relative path first
            direct_path = root_dir / query
            found_files = []
            
            if direct_path.is_file() and direct_path.resolve() != Path(output_filename).resolve():
                found_files = [direct_path]
            else:
                # Search recursively through subdirectories for matching file names
                found_files = [
                    f for f in root_dir.rglob(query) 
                    if f.is_file() and f.resolve() != Path(output_filename).resolve()
                ]
            
            if not found_files:
                print(f"  [Warning] '{query}' not found in the tree. Skipping.")
                continue
            
            selected_files = []
            
            # Handle single vs multiple matches
            if len(found_files) == 1:
                selected_files = found_files
                print(f"  Found uniquely at: {found_files[0]}")
            else:
                print(f"  [Notice] Multiple files match '{query}':")
                for idx, filepath in enumerate(found_files, 1):
                    print(f"    [{idx}] {filepath}")
                print(f"    [a] Include ALL matches")
                print(f"    [s] Skip this query")
                
                choice = input("  Select file number(s) (e.g. 1), 'a' for all, or 's' to skip: ").strip().lower()
                
                if choice == 'a':
                    selected_files = found_files
                elif choice == 's' or not choice:
                    print("  Skipped.")
                    continue
                else:
                    try:
                        # Allow selecting multiple indices separated by spaces
                        indices = [int(x) - 1 for x in choice.split()]
                        selected_files = [found_files[i] for i in indices if 0 <= i < len(found_files)]
                    except (ValueError, IndexError):
                        print("  [Error] Invalid selection. Skipping.")
                        continue

            # Write selected files to the output
            for filepath in selected_files:
                print(f"  Including: {filepath}")
                outfile.write(f"{filepath.name}\n```\n")
                
                try:
                    with open(filepath, 'r', encoding='utf-8') as infile:
                        content = infile.read()
                        outfile.write(content)
                        if not content.endswith('\n'):
                            outfile.write('\n')
                except UnicodeDecodeError:
                    print(f"  [Error] Could not read '{filepath.name}' as text.")
                    outfile.write("Error: Could not read file content.\n")
                
                outfile.write("```\n\n")

    print(f"\nSuccess! All selected files have been compiled into '{output_filename}'.")

if __name__ == "__main__":
    main()