# 📰 Newsletter Management System — Docker + Render Deployment

## Project Structure
```
newsletter_docker/
├── Dockerfile              ← PHP 8.2 + Apache + MongoDB ext
├── docker-compose.yml      ← Local development with MongoDB
├── render.yaml             ← Render deployment config
├── .dockerignore
├── .gitignore
├── .env.example            ← Copy to .env for local use
├── docker/
│   ├── apache.conf         ← Apache virtual host
│   ├── php.ini             ← PHP settings
│   └── start.sh            ← Container startup script
├── backend/                ← PHP API
└── frontend/               ← HTML/CSS/JS
```

---

## PART 1 — Run Locally with Docker

### Prerequisites
- Install **Docker Desktop**: https://www.docker.com/products/docker-desktop/
- Install **Git**: https://git-scm.com/downloads

### Steps

**1. Copy env file**
```bash
cp .env.example .env
# Edit .env with your SMTP credentials
```

**2. Build and run**
```bash
docker-compose up --build
```

**3. Open browser**
```
http://localhost:8080
```

**Login:**
- Email: `admin@college.edu`
- Password: `Admin@123`

**4. Stop**
```bash
docker-compose down
```

---

## PART 2 — Deploy to Render

### Step 1 — Create MongoDB Atlas (Free Cloud Database)

1. Go to **https://cloud.mongodb.com**
2. Sign up free → Create a new project
3. Click **Build a Database** → Choose **FREE (M0)** tier
4. Select region closest to you → Click **Create**
5. Set username & password (save these!)
6. Under **Network Access** → Add IP → **Allow access from anywhere** (`0.0.0.0/0`)
7. Click **Connect** → **Drivers** → Copy the connection string:
   ```
   mongodb+srv://username:password@cluster.mongodb.net/newsletter_db
   ```
   Replace `<password>` with your actual password

### Step 2 — Push code to GitHub

```bash
# Initialize git repo in your project folder
git init
git add .
git commit -m "Initial commit"

# Create a repo on github.com then:
git remote add origin https://github.com/YOUR_USERNAME/newsletter-system.git
git push -u origin main
```

### Step 3 — Deploy on Render

1. Go to **https://render.com** → Sign up free
2. Click **New +** → **Web Service**
3. Connect your GitHub account → Select your repo
4. Fill in settings:
   - **Name**: `newsletter-system` (or any name)
   - **Runtime**: `Docker`
   - **Branch**: `main`
   - **Plan**: `Free`
5. Click **Create Web Service**

### Step 4 — Set Environment Variables on Render

In your Render service → **Environment** tab → Add these variables:

| Key | Value |
|-----|-------|
| `MONGO_URI` | `mongodb+srv://user:pass@cluster.mongodb.net/newsletter_db` |
| `JWT_SECRET` | Any long random string (e.g. `xK9mP2qR7vN4wL8jH3tY6uA1sD5fG0`) |
| `SMTP_HOST` | `smtp.gmail.com` |
| `SMTP_PORT` | `587` |
| `SMTP_USER` | `youremail@gmail.com` |
| `SMTP_PASSWORD` | Your Gmail App Password |
| `FROM_EMAIL` | `youremail@gmail.com` |
| `FROM_NAME` | `Newsletter Management System` |
| `FRONTEND_URL` | `https://newsletter-system.onrender.com` ← your render URL |

Click **Save Changes** → Render will redeploy automatically

### Step 5 — Access your live site

Your app will be live at:
```
https://newsletter-system.onrender.com
```

Login with:
- Email: `admin@college.edu`
- Password: `Admin@123`

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Build fails: `pecl install mongodb` | Check Dockerfile — needs `libssl-dev` and `pkg-config` |
| MongoDB connection refused | Make sure Atlas IP whitelist includes `0.0.0.0/0` |
| App crashes on Render | Check Render logs → Dashboard → Logs tab |
| Images not loading after redeploy | Render free tier doesn't persist files — use Cloudinary for images |
| App sleeps after 15 min (free tier) | Normal on Render free — first request after sleep is slow |
| CORS errors | Check `FRONTEND_URL` env var matches your actual Render URL |

---

## Important Notes for Render Free Tier

1. **Uploaded images** — Render free tier does NOT persist uploaded files between deploys.
   For production, integrate **Cloudinary** or **AWS S3** for image storage.

2. **Sleep mode** — Free services sleep after 15 min of inactivity. First load takes ~30 sec to wake up.
   Upgrade to Starter ($7/mo) to avoid this.

3. **MongoDB** — Always use **MongoDB Atlas** (cloud), never a local MongoDB, for Render deployment.

---

## Admin Credentials
- Email: `admin@college.edu`
- Password: `Admin@123`
