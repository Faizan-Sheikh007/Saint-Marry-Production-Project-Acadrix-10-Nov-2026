"""Compatibility entry point for the current audience-specific print checks."""
import runpy
from pathlib import Path
runpy.run_path(str(Path(__file__).with_name("browser_receipt_office.py")), run_name="__main__")
