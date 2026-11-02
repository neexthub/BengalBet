import dotenv from 'dotenv';
dotenv.config();

import express, { Request, Response } from 'express';
import path from 'path';
import { createServer as createViteServer } from 'vite';

async function startServer() {
  const app = express();
  const PORT = process.env.PORT ? parseInt(process.env.PORT, 10) : 3000;

  app.use(express.json());

  // Health check
  app.get('/api/health', (req: Request, res: Response) => {
    res.json({
      status: 'ok',
      service: 'BengalBet Frontend & OAuth Server',
      restApiTarget: 'https://deserver.neexthub.site/dbserver/',
      time: new Date().toISOString()
    });
  });

  // Helper for Google OAuth redirect URI
  const getRedirectUri = (req: Request): string => {
    const originQuery = req.query.origin as string;
    if (originQuery && originQuery.startsWith('http')) {
      return `${originQuery.replace(/\/$/, '')}/auth/callback`;
    }
    if (process.env.APP_URL) {
      return `${process.env.APP_URL.replace(/\/$/, '')}/auth/callback`;
    }
    const host = req.get('host') || `localhost:${PORT}`;
    const proto = req.get('x-forwarded-proto') || (req.secure ? 'https' : 'http');
    return `${proto}://${host}/auth/callback`;
  };

  // ==========================================
  // GOOGLE OAUTH 2.0 ROUTES
  // ==========================================

  // 1. Get Google OAuth URL
  app.get('/api/auth/google/url', (req: Request, res: Response) => {
    const clientId = process.env.GOOGLE_CLIENT_ID || process.env.CLIENT_ID;
    const redirectUri = getRedirectUri(req);

    if (!clientId) {
      return res.json({
        configured: false,
        message: 'Google Client ID is not configured in environment variables.',
        redirectUri
      });
    }

    const params = new URLSearchParams({
      client_id: clientId,
      redirect_uri: redirectUri,
      response_type: 'code',
      scope: 'openid email profile',
      access_type: 'offline',
      prompt: 'select_account'
    });

    const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?${params.toString()}`;

    res.json({
      configured: true,
      url: authUrl,
      redirectUri
    });
  });

  // 2. Google OAuth Callback (Popup target)
  app.get(['/auth/callback', '/auth/callback/'], async (req: Request, res: Response) => {
    const { code, error } = req.query;
    const clientId = process.env.GOOGLE_CLIENT_ID || process.env.CLIENT_ID;
    const clientSecret = process.env.GOOGLE_CLIENT_SECRET || process.env.CLIENT_SECRET;
    const redirectUri = getRedirectUri(req);

    if (error) {
      return res.send(`
        <!DOCTYPE html>
        <html>
          <head><title>Google Sign-In Error</title></head>
          <body style="font-family: sans-serif; background: #062016; color: #fff; text-align: center; padding: 40px;">
            <h3 style="color: #f87171;">Google Login Canceled or Failed</h3>
            <p>${error}</p>
            <script>
              if (window.opener) {
                window.opener.postMessage({ type: 'OAUTH_AUTH_ERROR', error: '${error}' }, '*');
                setTimeout(() => window.close(), 1200);
              }
            </script>
          </body>
        </html>
      `);
    }

    if (!code) {
      return res.send(`
        <!DOCTYPE html>
        <html>
          <head><title>Google Sign-In Missing Code</title></head>
          <body style="font-family: sans-serif; background: #062016; color: #fff; text-align: center; padding: 40px;">
            <h3 style="color: #fbbf24;">কোনো অথোরাইজেশন কোড পাওয়া যায়নি</h3>
            <script>
              if (window.opener) {
                window.opener.postMessage({ type: 'OAUTH_AUTH_ERROR', error: 'No code returned' }, '*');
                setTimeout(() => window.close(), 1200);
              }
            </script>
          </body>
        </html>
      `);
    }

    try {
      if (!clientId || !clientSecret) {
        throw new Error('GOOGLE_CLIENT_ID or GOOGLE_CLIENT_SECRET is missing on the server.');
      }

      // Exchange code for token
      const tokenRes = await fetch('https://oauth2.googleapis.com/token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          code: code as string,
          client_id: clientId,
          client_secret: clientSecret,
          redirect_uri: redirectUri,
          grant_type: 'authorization_code'
        })
      });

      if (!tokenRes.ok) {
        const errorText = await tokenRes.text();
        console.error('Google token exchange failed:', errorText);
        throw new Error(`Google token error: ${tokenRes.status}`);
      }

      const tokenJson = await tokenRes.json();
      const accessToken = tokenJson.access_token;

      // Get Google user info
      const profileRes = await fetch('https://www.googleapis.com/oauth2/v3/userinfo', {
        headers: { Authorization: `Bearer ${accessToken}` }
      });

      if (!profileRes.ok) {
        throw new Error('Failed to fetch user info from Google');
      }

      const profile = await profileRes.json();
      const email = profile.email;
      const name = profile.name || `User_${email.split('@')[0]}`;
      const avatar = profile.picture || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120&auto=format&fit=crop&q=80';

      const randomDigits = Math.floor(10000000 + Math.random() * 90000000);
      const userObj = {
        id: `usr-${randomDigits}`,
        username: name.slice(0, 16).replace(/\s+/g, '_'),
        phone: `017${randomDigits}`,
        email: email,
        balance: 1000.00,
        vipLevel: 1,
        vipExp: 1000,
        vipMaxExp: 5000,
        freeSpins: 5,
        redPacketsAvailable: 2,
        goldenEggsAvailable: 2,
        unreadNotifications: 3,
        referralCode: `BDT${randomDigits.toString().slice(-3)}`,
        totalEarnings: 0,
        referredFriendsCount: 0,
        avatar: avatar,
        isLoggedIn: true
      };

      const safeUser = JSON.stringify(userObj);

      res.send(`
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="utf-8">
            <title>Google Sign-In Successful</title>
            <style>
              body {
                background: #062016;
                color: #ecfdf5;
                font-family: system-ui, -apple-system, sans-serif;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
                text-align: center;
              }
              .spinner {
                width: 44px;
                height: 44px;
                border: 4px solid rgba(250, 204, 21, 0.2);
                border-top-color: #facc15;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
                margin-bottom: 18px;
              }
              @keyframes spin { to { transform: rotate(360deg); } }
              h2 { color: #facc15; margin: 0 0 8px; font-size: 22px; }
              p { color: #a7f3d0; font-size: 14px; margin: 0; }
            </style>
          </head>
          <body>
            <div class="spinner"></div>
            <h2>Google দিয়ে সাইন ইন সফল হয়েছে!</h2>
            <p>অনুগ্রহ করে অপেক্ষা করুন, উইন্ডোটি স্বয়ংক্রিয়ভাবে বন্ধ হবে...</p>
            <script>
              try {
                if (window.opener) {
                  window.opener.postMessage({
                    type: 'OAUTH_AUTH_SUCCESS',
                    provider: 'google',
                    token: '${userObj.id}',
                    user: ${safeUser}
                  }, '*');
                  setTimeout(function() { window.close(); }, 600);
                } else {
                  window.location.href = '/';
                }
              } catch (e) {
                console.error(e);
                window.location.href = '/';
              }
            </script>
          </body>
        </html>
      `);
    } catch (err: any) {
      console.error('Google OAuth callback handler error:', err);
      res.status(500).send(`
        <!DOCTYPE html>
        <html>
          <head><title>Google Sign-In Error</title></head>
          <body style="font-family: sans-serif; background: #062016; color: #fff; text-align: center; padding: 40px;">
            <h3 style="color: #f87171;">Google প্রমাণীকরণ ত্রুটি</h3>
            <p style="color: #fca5a5;">${err.message || 'Error processing Google sign-in'}</p>
            <script>
              if (window.opener) {
                window.opener.postMessage({ type: 'OAUTH_AUTH_ERROR', error: '${err.message}' }, '*');
                setTimeout(() => window.close(), 2500);
              }
            </script>
          </body>
        </html>
      `);
    }
  });

  // Demo Google Sign-In helper
  app.post('/api/auth/google/demo', (req: Request, res: Response) => {
    const { email, name, avatar } = req.body;
    const userEmail = email || 'workpkx1@gmail.com';
    const userName = name || userEmail.split('@')[0];
    const userAvatar = avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120&auto=format&fit=crop&q=80';
    const randomDigits = Math.floor(10000000 + Math.random() * 90000000);

    const userObj = {
      id: `usr-${randomDigits}`,
      username: userName.slice(0, 16).replace(/\s+/g, '_'),
      phone: `017${randomDigits}`,
      email: userEmail,
      balance: 1000.00,
      vipLevel: 1,
      vipExp: 1000,
      vipMaxExp: 5000,
      freeSpins: 5,
      redPacketsAvailable: 2,
      goldenEggsAvailable: 2,
      unreadNotifications: 3,
      referralCode: `BDT${randomDigits.toString().slice(-3)}`,
      totalEarnings: 0,
      referredFriendsCount: 0,
      avatar: userAvatar,
      isLoggedIn: true
    };

    res.json({
      success: true,
      message: 'Google দিয়ে সফলভাবে লগইন সম্পন্ন হয়েছে!',
      token: userObj.id,
      user: userObj
    });
  });

  // ==========================================
  // VITE DEV & STATIC PRODUCTION SERVING
  // ==========================================
  if (process.env.NODE_ENV !== 'production') {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa'
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), 'dist');
    app.use(express.static(distPath));
    app.get('*', (req: Request, res: Response) => {
      res.sendFile(path.join(distPath, 'index.html'));
    });
  }

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`BengalBet Dev Server running on http://0.0.0.0:${PORT}`);
  });
}

startServer().catch(err => {
  console.error('Failed to start server:', err);
});
