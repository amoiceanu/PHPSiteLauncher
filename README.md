# PHP Site Launcher

Serve the `public/` directory as the web root with PHP session support.
The public entry point handles authentication and renders configured application links.

Copy `applications.example.json` to `applications.json`, then set each repository path and deployed URL. The local configuration is ignored by Git. Unavailable repositories appear disabled.

The logout link destroys the launcher session, removes its cookie, and returns to the login form.
