"""
Quick test of multi_test.py
"""
import subprocess
import sys

result = subprocess.run(
    [sys.executable, "multi_test.py"],
    capture_output=True,
    text=True,
    timeout=30
)

print("STDOUT:")
print(result.stdout)
print("\nSTDERR:")
print(result.stderr)
print(f"\nReturn code: {result.returncode}")

