# W3P Admin Login

**W3P Admin Login** is an easy-to-use WordPress and ClassicPress plugin that allows you to safely change your website's admin login URL to anything you want.

The plugin's simple two-step process ensures the safety of your WordPress/ClassicPress admin login URL within seconds, and all of this without any coding.

Fully compatible with **Git Updater**.

## Security against malicious activity

Anyone can find your WordPress/ClassicPress website's default login page, and this can increase the chances of security breaches like brute force attacks and other cyber threats. The **W3P Admin Login** plugin allows you to change your admin login URL and redirect any user to a redirection URL.

The **W3P Admin Login** plugin does not rename or physically change any files in the core, but instead simply intercepts page requests and redirects to another URL.

## Why use W3P Admin Login plugin?

- Protect your WordPress/ClassicPress website from brute force attacks.
- Quick two-step process that doesn't require coding.
- Easy to secure your WordPress/ClassicPress website from hackers and cyberattacks.
- Only grant access to people you trust.
- Hide your login page from malicious activity.
- Easier than creating a custom login URL.

## How it works?

Go under **Settings**, click **Permalinks** and change your URL under "W3P Admin Login" section.

- **Step 1:** Add a new login URL
- **Step 2:** Add redirect URL

### Redirect Custom Field

Accessing the `wp-login.php` page or the `/wp-admin/` directory without logging in will redirect you to the page defined in the redirect custom field. Leaving the redirect custom field empty will activate the default settings (redirect to the website's homepage).

---

> **Note** – After you activate this plugin, the `/wp-admin/` directory and wp-login.php page will become unavailable, so you should bookmark or remember the URL. Disabling this plugin brings your site back exactly to its previous state.

## Frequently Asked Questions

**Why can't I log in?**  
In case you forgot the login URL or, for any other reason, you can't log into the website, you will need to delete (or rename) the plugin via (S)FTP or File Manager on your hosting panel.

The path for the plugin folder is `/wp-content/plugins/w3p-admin-login`

**Does it work on WordPress/ClassicPress Multisite with Subdirectories?**  
Yes, it does work. You should set up the login URL in each website (**Settings** → **Permalinks**)

**Does it work on WordPress/ClassicPress Multisite with Subdomains?**  
Yes, it does work. You should set up the login URL in each website (**Settings** → **Permalinks**)
