import os
BASE = 'C:/laragon/www/asim-projects/religious-management-system/docs/testing'

def w(name, lines):
    path = os.path.join(BASE, name)
    with open(path, 'w', encoding='utf-8') as f:
        f.write('
'.join(lines))
    print(f'Written {name} ({os.path.getsize(path)} bytes)')

