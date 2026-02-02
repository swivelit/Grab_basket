import shutil
import os

# Set your project root and image filename
project_root = r'E:\e-com_updated_final\e-com_updated'
source_image = r'PATH_TO_YOUR_IMAGE\IWTuS7V8TkGPv4tj0W8U4IMPjYFzBqnyIU3JaHpU.jpg'  # Change this to your actual image path

dest_dir = os.path.join(project_root, 'storage', 'app', 'public', 'products')
dest_image = os.path.join(dest_dir, os.path.basename(source_image))

# Ensure destination directory exists
os.makedirs(dest_dir, exist_ok=True)

# Copy the image
shutil.copy2(source_image, dest_image)
print(f'Image copied to: {dest_image}')

# Verify retrieval (list all images in the directory)
print('Files in products directory:')
for f in os.listdir(dest_dir):
    print(f)
