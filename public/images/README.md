# Profile photo

Drop your portrait here as **`profile.jpg`** (or `profile.png` — update the
component `src` if you change the extension).

```bash
# from the repo root:
cp <your-photo-file> public/images/profile.jpg
```

The Vue `ProfilePhoto` component loads `/images/profile.jpg`. If the file is
missing, it gracefully falls back to a "YD" monogram, so the site still renders.

Recommended: square crop, ~800×800px, JPEG, < 300 KB.
