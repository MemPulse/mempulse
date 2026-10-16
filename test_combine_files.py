import unittest
from unittest.mock import patch, mock_open
from pathlib import Path
import sys

import comebine_files

class TestCombineFiles(unittest.TestCase):
    
    @patch("builtins.input", side_effect=["output.txt", "admin/views/index.php"])
    @patch("builtins.print")
    @patch("pathlib.Path.is_file", return_value=True)
    @patch("builtins.open", new_callable=mock_open, read_data="test file content")
    def test_direct_relative_path(self, mock_file, mock_is_file, mock_print, mock_input):
        comebine_files.main()
        mock_file.assert_called()

    @patch("builtins.input", side_effect=["output.txt", "index.php", "1"])
    @patch("builtins.print")
    @patch("builtins.open", new_callable=mock_open, read_data="duplicate file content")
    def test_duplicate_file_selection(self, mock_file, mock_print, mock_input):
        fake_paths = [Path("admin/views/index.php"), Path("app/views/index.php")]
        with patch("pathlib.Path.rglob", return_value=fake_paths), \
             patch("pathlib.Path.is_file", return_value=True):
            comebine_files.main()
        mock_file.assert_called()

    @patch("builtins.input", side_effect=["output.txt", "nonexistent.php"])
    @patch("builtins.print")
    def test_missing_file_handling(self, mock_print, mock_input):
        with patch("pathlib.Path.rglob", return_value=[]), \
             patch("pathlib.Path.is_file", return_value=False):
            comebine_files.main()
        output_logs = [call.args[0] for call in mock_print.mock_calls if call.args]
        warning_logged = any("[Warning]" in log for log in output_logs)
        self.assertTrue(warning_logged)

if __name__ == "__main__":
    unittest.main()
