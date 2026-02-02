import shutil
import os
glob_patterns = [
    'Chocolate/Images',
    'images',
    'public/images',
    'public/images/srm',
    'SRM IMG',
    'public/asset/images',
]
project_root = r'E:/e-com_updated_final/e-com_updated'
dest_dir = os.path.join(project_root, 'storage', 'app', 'public', 'products')
os.makedirs(dest_dir, exist_ok=True)
exts = ('.jpg', '.jpeg', '.png', '.gif')
count = 0
for rel_folder in glob_patterns:
    src_folder = os.path.join(project_root, rel_folder)
    if not os.path.exists(src_folder):
        continue
    for root, dirs, files in os.walk(src_folder):
        for file in files:
            if file.lower().endswith(exts):
                src_file = os.path.join(root, file)
                dest_file = os.path.join(dest_dir, file)
                if not os.path.exists(dest_file):
                    shutil.copy2(src_file, dest_file)
                    count += 1
                    print(f'Copied: {src_file} -> {dest_file}')
                else:
                    print(f'Skipped (already exists): {dest_file}')
print(f'\nTotal images copied: {count}')
print('All done!')
