import os

BASE = "C:/laragon/www/asim-projects/religious-management-system/docs/testing"

def w(name, content):
    with open(os.path.join(BASE, name), "w", encoding="utf-8") as fh:
        fh.write(content)
    print(f"Written: {name} ({len(content)} chars)")

print("Generator loaded OK")
