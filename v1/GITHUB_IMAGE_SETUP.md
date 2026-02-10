# GitHub Image Storage Setup Guide

## 1. Create GitHub Repository for Images

1. Go to https://github.com/grabbaskets-hash
2. Click "New repository"
3. Name: `grabbaskets-images`
4. Set to Public (so images can be accessed without authentication)
5. Initialize with README
6. Create repository

## 2. Generate GitHub Personal Access Token

1. Go to GitHub Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Click "Generate new token (classic)"
3. Give it a name: "GrabBaskets Image Storage"
4. Select scopes:
   - `repo` (Full control of private repositories)
   - `public_repo` (Access public repositories)
5. Click "Generate token"
6. Copy the token (starts with `ghp_...`)

## 3. Update .env File

Add your GitHub token to .env:
```
GITHUB_TOKEN=your_actual_token_here
GITHUB_OWNER=grabbaskets-hash
GITHUB_REPO=grabbaskets-images
```

## 4. Test the Setup

Once configured, your product images will be:
- Uploaded to: https://github.com/grabbaskets-hash/grabbaskets-images/tree/main/products
- Served from: https://raw.githubusercontent.com/grabbaskets-hash/grabbaskets-images/main/products/[filename]

## Features

✅ Automatic image upload to GitHub
✅ Unique filenames to prevent conflicts  
✅ Error handling and fallbacks
✅ Logging for debugging
✅ Image deletion support
✅ CDN-like delivery via GitHub raw URLs

## Benefits

- Free image hosting
- Global CDN via GitHub
- Version control for images
- No storage limits
- Fast delivery
- Reliable uptime