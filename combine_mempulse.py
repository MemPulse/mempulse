import os
from pathlib import Path

# Target extensions to include
ALLOWED_EXTENSIONS = {'.php', '.js', '.css', '.html'}

# Name of the compiled output file
OUTPUT_FILE = "mempulse_combined_code.txt"

def combine_code_files():
    # Get the directory where this script is executed
    base_dir = Path(__file__).resolve().parent
    output_path = base_dir / OUTPUT_FILE
    
    compiled_content = []
    file_count = 0

    # Recursively traverse all subdirectories
    for file_path in base_dir.rglob('*'):
        # Check if it's a file and matches allowed extensions
        if file_path.is_file() and file_path.suffix.lower() in ALLOWED_EXTENSIONS:
            # Avoid reading the output file itself if it matches criteria
            if file_path.resolve() == output_path.resolve():
                continue

            # Calculate relative path (e.g., "app/api/DeckService.php")
            rel_path = file_path.relative_to(base_dir)
            
            try:
                # Read content with UTF-8 encoding
                content = file_path.read_text(encoding='utf-8', errors='replace')
                
                # Format header and content for clarity
                header = f"\n{'='*80}\nFILE: {rel_path}\n{'='*80}\n"
                compiled_content.append(header + content + "\n")
                file_count += 1
                print(f"Added: {rel_path}")
            except Exception as e:
                print(f"Skipped {rel_path} (Error: {e})")

    # Write all compiled contents into the output file
    output_path.write_text("\n".join(compiled_content), encoding='utf-8')
    print(f"\nSuccess! Compiled {file_count} files into '{OUTPUT_FILE}'.")

if __name__ == "__main__":
    combine_code_files()