# Azure Application Insights for WordPress

![screenshot](./screenshot.png)

### What's tracked:
1. Page Views
    * Page Title
    * Page URL
    * Duration
    * Referrer
    * URL Path 
    * Device Type: e.g. Browser
    * Device Model
    * Device OS
    * IP Address
    * Location: City
    * Location: StateOrProvince
    * Location: Country
    * Device Type Version
2. Browser Timings (how long the request takes to send and receive)
   * Page Title
   * Page URL
   * Duration Network Call: Completed
   * Duration Network Call: Send
   * Duration Network Call: Receive
   * Device Type: Browser
   * Device Type Version
   * Device OS
3. Custom Events (requires extra code)
   * Event Name
   * URL Path
   * Device Type: e.g. Browser
   * Device Model
   * Device OS
   * IP Address
   * Location: City
   * Location: StateOrProvince
   * Location: Country
   * Device Type Version
4. Dependencies
5. Browser Exceptions
   * Error Message
   * Error Script
   * Error Stack Trace
   * URL Path
   * Device Type: e.g. Browser
   * Device Model
   * Device OS
   * IP Address
   * Location: City
   * Location: StateOrProvince
   * Location: Country
   * Device Type Version
6. Performance Counters
7. Requests
8. Traces
   * Message
   * Severity Level
   * URL Path
   * Device Type: e.g. Browser
   * Device Model
   * Device OS
   * IP Address
   * Location: City
   * Location: StateOrProvince
   * Location: Country
   * Device Type Version
9. WordPress Events
   * User
     * Login Success
     * Login Failed
     * Logout
     *  New Registration
   * Plugin
     * Activated
     * Deactivated
     * Deleted
   * Upgrade Events:
     * Theme
     * Plugin
     * Translations
   * Theme:
     * Deleted
     * Switched

---

Javascript snippet taken from:

https://github.com/microsoft/ApplicationInsights-JS

---

### TODO:
*  [ ] Prevent sending data to source if there is no internet