import os
from pathlib import Path

def main():
    output_filename = input("Enter the filename for the output (e.g., combined.txt): ")
    
    print("\nType the filenames you want to find and include (separated by spaces).")
    print("Example: deck.php deck-view.php")
    
    inputs = input("> ").strip().split()
    
    if not inputs:
        print("No files provided. Exiting.")
        return

    # Use the directory where the script is run as the root search tree
    root_dir = Path('.')

    with open(output_filename, 'w', encoding='utf-8') as outfile:
        for file_name in inputs:
            print(f"\nSearching for '{file_name}'...")
            
            # Search recursively through all subdirectories
            found_files = list(root_dir.rglob(file_name))
            
            if not found_files:
                print(f"  [Warning] '{file_name}' not found in the tree. Skipping.")
                continue
            
            for filepath in found_files:
                # Skip the output file if it happens to share the same name
                if filepath.resolve() == Path(output_filename).resolve():
                    continue
                    
                print(f"  Found at: {filepath}")
                
                # Write the filename and opening code block
                outfile.write(f"{filepath.name}\n```\n")
                
                try:
                    # Write the file content
                    with open(filepath, 'r', encoding='utf-8') as infile:
                        content = infile.read()
                        outfile.write(content)
                        
                        # Ensure there's a newline before the closing backticks
                        if not content.endswith('\n'):
                            outfile.write('\n')
                except UnicodeDecodeError:
                    print(f"  [Error] Could not read '{filepath.name}' as text.")
                    outfile.write("Error: Could not read file content.\n")
                
                # Write the closing code block
                outfile.write("```\n\n")

    print(f"\nSuccess! All found files have been compiled into '{output_filename}'.")

if __name__ == "__main__":
    main()