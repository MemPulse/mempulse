from pathlib import Path

ALLOWED_EXTENSIONS = {'.php', '.html', '.css', '.js'}

def main():
    while True:
        user_input = input("\nEnter parent folder path (or 'q' to exit): ").strip()

        # Check for exit command
        if user_input.lower() in {'q', 'quit', 'exit'}:
            print("Exiting program.")
            break

        folder_path = Path(user_input)

        # Validate directory
        if not folder_path.exists() or not folder_path.is_dir():
            print("Error: Directory does not exist. Please try again.")
            continue

        # Scan non-nested immediate children only
        matching_files = [
            f.name for f in folder_path.iterdir()
            if f.is_file() and f.suffix.lower() in ALLOWED_EXTENSIONS
        ]

        # Output results
        if matching_files:
            print("\nFound files:")
            print(", ".join(matching_files))
        else:
            print("\nNo matching (.php, .html, .css, .js) files found in this folder.")

if __name__ == "__main__":
    main()