<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For development/testing, set default values
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['email'] = 'admin@example.com';
    
    // Uncomment the following lines for production:
    // header("Location: home.php");
    // exit;
}

// Get user info from session
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSaur - Chatbot & AI Training Logs</title>
    <style>
/* Dark Theme (Default) - Softer colors */
.dark-theme {
    --color-bg: #1A211A;
    --color-card: #2A3326;
    --color-highlight: #A1B454;
    --color-text: #E8F0D6;
    --color-accent1: #8CA86E;
    --color-accent2: #B5C88D;
    --color-accent3: #546048;
    --color-accent4: #C9D8AA;
    --color-danger: #CF8686;
    --color-warning: #E0C989;
}

/* Light Theme - Softer colors */
.light-theme {
    --color-bg: #a0ca3f;
    --color-card: #EAF0DC;
    --color-highlight: #8EB96E;
    --color-text: #415939;
    --color-accent1: #F9B97F;
    --color-accent2: #E9957C;
    --color-accent3: #76BB6E;
    --color-accent4: #D7E3A0;
    --color-danger: #E98D7C;
    --color-warning: #F9C87F;
}

.light-theme body {
    background: linear-gradient(135deg, #DCE8C0, #C5DBA1);
    background-size: 400% 400%;
    animation: gradientBackground 15s ease infinite;
    background-image: none;
}

@keyframes gradientBackground {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
}

body {
    min-height: 100vh;
    background-color: var(--color-bg);
    color: var(--color-text);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
    padding-left: 320px;
    line-height: 1.6;
    letter-spacing: 0.2px;
}

/* Navbar Styles */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 320px;
    height: 100vh;
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
    padding: 0;
    box-sizing: border-box;
    overflow-y: auto;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
}

.navbar-header {
    padding: 35px 25px;
    display: flex;
    align-items: center;
    border-bottom: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    position: relative;
    overflow: hidden;
}

.navbar-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

.light-theme .navbar-header {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.05) 0%, transparent 100%);
}

.light-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.3), transparent);
}

.navbar-logo {
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
}

.navbar-logo:hover {
    transform: scale(1.05);
}

.navbar-logo-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-right: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 20px;
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.1);
}

.navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.08));
    border-color: rgba(161, 180, 84, 0.3);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.2);
}

.light-theme .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.1), rgba(142, 185, 110, 0.05));
    border-color: rgba(142, 185, 110, 0.2);
    box-shadow: 0 2px 8px rgba(142, 185, 110, 0.1);
}

.light-theme .navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.15), rgba(142, 185, 110, 0.08));
    border-color: rgba(142, 185, 110, 0.3);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.2);
}

.navbar-logo-text {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.navbar-menu {
    flex: 1;
    padding: 30px 0;
}

.navbar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.navbar li {
    margin-bottom: 2px;
    position: relative;
    transition: all 0.3s ease;
}

.navbar li:hover {
    transform: translateX(5px);
}

.navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(161, 180, 84, 0.08);
}

.light-theme .navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(142, 185, 110, 0.08);
}

.navbar a {
    text-decoration: none;
    color: var(--color-text);
    font-size: 17px;
    padding: 18px 25px;
    display: flex;
    align-items: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    opacity: 0.9;
    border-radius: 0 12px 12px 0;
    margin-right: 10px;
    overflow: hidden;
    background: linear-gradient(90deg, transparent 0%, transparent 100%);
}

.navbar a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

.light-theme .navbar a::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.1), transparent);
}

.navbar a:hover {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.08) 0%, rgba(161, 180, 84, 0.04) 100%);
    color: var(--color-highlight);
    opacity: 1;
    transform: translateX(3px);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.15);
}

.navbar a:hover::before {
    left: 100%;
}

.navbar a.active {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.08) 100%);
    color: var(--color-highlight);
    opacity: 1;
    font-weight: 600;
    border-left: 4px solid var(--color-highlight);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.2);
    transform: translateX(2px);
}

.light-theme .navbar a:hover {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.08) 0%, rgba(142, 185, 110, 0.04) 100%);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.15);
}

.light-theme .navbar a.active {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.15) 0%, rgba(142, 185, 110, 0.08) 100%);
    border-left-color: var(--color-accent3);
    box-shadow: 0 6px 20px rgba(142, 185, 110, 0.2);
}

.navbar-icon {
    margin-right: 15px;
    width: 24px;
    font-size: 20px;
}

.navbar-footer {
    padding: 25px 20px;
    border-top: 2px solid rgba(164, 188, 46, 0.15);
    font-size: 12px;
    opacity: 0.7;
    text-align: center;
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
    position: relative;
}

.navbar-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

.light-theme .navbar-footer {
    background: linear-gradient(135deg, transparent 0%, rgba(142, 185, 110, 0.03) 100%);
}

.light-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.2), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

.light-theme .navbar-footer div:first-child {
    color: var(--color-accent3);
}

.light-theme body {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" opacity="0.1"><path d="M10,10 Q50,20 90,10 Q80,50 90,90 Q50,80 10,90 Q20,50 10,10 Z" fill="%2376BB43"/></svg>');
    background-size: 300px;
}

.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.light-theme header {
    background-color: var(--color-card);
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.logo {
    display: flex;
    align-items: center;
}

        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-right: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--color-text);
            font-weight: bold;
        }

.light-theme .logo-icon {
    color: white;
}

h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.light-theme h1 {
    color: var(--color-highlight);
}

.user-info {
    display: flex;
    align-items: center;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    margin-right: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
}

.light-theme .user-avatar {
    background-color: var(--color-accent1);
    color: white;
    font-weight: bold;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    margin-bottom: 30px;
}

.feature-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
}

.light-theme .feature-card {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background-color: rgba(161, 180, 84, 0.2);
    border-radius: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    font-size: 30px;
}

.light-theme .feature-icon {
    background-color: rgba(142, 185, 110, 0.2);
}

.feature-card h3 {
    font-size: 20px;
    margin-bottom: 15px;
    color: var(--color-highlight);
}

.feature-card p {
    font-size: 15px;
    opacity: 0.9;
    margin-bottom: 20px;
    flex-grow: 1;
}

.feature-action {
    display: flex;
    align-items: center;
    font-weight: 500;
    color: var(--color-highlight);
    margin-top: auto;
}

.feature-action span {
    margin-left: 8px;
    font-size: 18px;
}

/* Chat logs container */
.chat-logs-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .chat-logs-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.chat-logs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.chat-logs-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.chat-logs-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

.chat-filter {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: rgba(161, 180, 84, 0.1);
    padding: 8px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chat-filter:hover {
    background-color: rgba(161, 180, 84, 0.2);
}

.chat-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.chat-item {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 0;
    overflow: hidden;
}

.light-theme .chat-item {
    background-color: rgba(234, 240, 220, 0.7);
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: rgba(0, 0, 0, 0.1);
    cursor: pointer;
}

.chat-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.chat-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 14px;
}

.light-theme .chat-avatar {
    color: white;
}

.chat-meta {
    font-size: 12px;
    opacity: 0.7;
}

.chat-rating {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-high {
    color: var(--color-accent3);
}

.rating-medium {
    color: var(--color-warning);
}

.rating-low {
    color: var(--color-danger);
}

.chat-content {
    display: none;
    padding: 15px 20px;
    max-height: 400px;
    overflow-y: auto;
}

.chat-content.active {
    display: block;
}

.chat-message {
    display: flex;
    margin-bottom: 15px;
}

.chat-message.user {
    flex-direction: row-reverse;
}

.message-bubble {
    max-width: 80%;
    padding: 12px;
    border-radius: 10px;
    position: relative;
}

.user .message-bubble {
    background-color: rgba(161, 180, 84, 0.3);
    border-top-right-radius: 0;
}

.ai .message-bubble {
    background-color: rgba(42, 51, 38, 0.6);
    border-top-left-radius: 0;
}

.light-theme .ai .message-bubble {
    background-color: rgba(142, 185, 110, 0.2);
}

.message-time {
    font-size: 10px;
    opacity: 0.7;
    margin-top: 5px;
    text-align: right;
}

.chat-action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 0 20px 15px;
}

.chat-action-btn {
    font-size: 12px;
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-text);
    transition: all 0.3s ease;
}

.chat-action-btn:hover {
    background-color: rgba(161, 180, 84, 0.4);
}



/* Training data container */
.training-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .training-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.training-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.training-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.training-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.training-tab {
    padding: 8px 15px;
    border-radius: 8px;
    background-color: rgba(161, 180, 84, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.training-tab.active {
    background-color: var(--color-highlight);
    color: white;
}

.light-theme .training-tab.active {
    color: var(--color-text);
}

.training-tab:hover:not(.active) {
    background-color: rgba(161, 180, 84, 0.2);
}

.training-content {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 20px;
}

.light-theme .training-content {
    background-color: rgba(234, 240, 220, 0.7);
}

.training-group {
    margin-bottom: 20px;
}

.training-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    cursor: pointer;
}

.training-group-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.training-group-icon {
    font-size: 18px;
    transition: transform 0.3s ease;
}

.training-group.open .training-group-icon {
    transform: rotate(90deg);
}

.training-group-content {
    display: none;
    border-left: 2px solid var(--color-highlight);
    padding-left: 15px;
    margin-left: 5px;
}

.training-group.open .training-group-content {
    display: block;
}

.training-item {
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 8px;
    background-color: rgba(161, 180, 84, 0.05);
}

.training-item-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.training-item-title {
    font-weight: 500;
}

.training-item-content {
    font-size: 14px;
    opacity: 0.9;
}

.training-item-responses {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(161, 180, 84, 0.2);
}

.training-response {
    font-size: 14px;
    margin-bottom: 8px;
    padding-left: 10px;
    position: relative;
}

.training-response::before {
    content: '•';
    position: absolute;
    left: 0;
    color: var(--color-highlight);
}

.training-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.training-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.primary-btn {
    background-color: var(--color-highlight);
    color: var(--color-text);
}

.light-theme .primary-btn {
    color: white;
}

.secondary-btn {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-text);
}

.training-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Improved navigation bar */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 320px;
    height: 100vh;
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
    padding: 0;
    box-sizing: border-box;
    overflow-y: auto;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    backdrop-filter: blur(10px);
}





@media (max-width: 768px) {
    .navbar {
        width: 80px;
        transform: translateX(0);
        transition: transform 0.3s ease, width 0.3s ease;
    }
    
    .navbar:hover {
        width: 320px;
    }
    
    .navbar-logo-text, .navbar span:not(.navbar-icon) {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .navbar:hover .navbar-logo-text, 
    .navbar:hover span:not(.navbar-icon) {
        opacity: 1;
    }
    
    body {
        padding-left: 100px;
    }

    .feature-grid, .analytics-grid {
        grid-template-columns: 1fr;
    }

    .navbar a {
        padding: 12px 25px;
    }
    
    .navbar li {
        margin-bottom: 2px;
    }
}

.light-theme .navbar {
    background-color: rgba(234, 240, 220, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 3px 0 20px rgba(0, 0, 0, 0.06);
}

/* Add this custom scrollbar styling */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #7cb342 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #7cb342 0%, #689f38 100%);
}

/* Chat interface styles */
.chat-container {
    background-color: var(--color-card);
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 200px);
    min-height: 600px;
    overflow: hidden;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 30px;
    display: flex;
    flex-direction: column;
}

.welcome-message {
    text-align: center;
    padding: 40px 20px;
    margin-bottom: 30px;
}

.welcome-message h2 {
    color: var(--color-highlight);
    font-size: 28px;
    margin-bottom: 15px;
}

.welcome-message p {
    color: var(--color-text);
    font-size: 16px;
    opacity: 0.9;
}

.message {
    display: flex;
    margin-bottom: 20px;
    animation: messageAppear 0.3s ease;
}

@keyframes messageAppear {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message.user-message {
    justify-content: flex-end;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    margin-right: 12px;
}

.message.user-message .message-avatar {
    background-color: var(--color-accent3);
    order: 2;
    margin-right: 0;
    margin-left: 12px;
}

.message.ai-message .message-avatar {
    background-color: var(--color-highlight);
}

.light-theme .message-avatar {
    color: white;
}

.message-content {
    max-width: 80%;
    padding: 15px;
    border-radius: 18px;
    position: relative;
}

.message.user-message .message-content {
    background-color: var(--color-highlight);
    color: var(--color-text);
    border-top-right-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.message.ai-message .message-content {
    background-color: rgba(42, 51, 38, 0.6);
    color: var(--color-text);
    border-top-left-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.light-theme .message.ai-message .message-content {
    background-color: rgba(142, 185, 110, 0.2);
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 6px;
    text-align: right;
    color: var(--color-text);
}

.message-typing {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 0 5px;
    background-color: rgba(42, 51, 38, 0.6);
    border-radius: 18px;
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.light-theme .message-typing {
    background-color: rgba(142, 185, 110, 0.2);
    border: 1px solid rgba(142, 185, 110, 0.3);
}

.chat-input-container {
    padding: 20px;
    border-top: 1px solid rgba(164, 188, 46, 0.2);
    display: flex;
    gap: 10px;
    align-items: center;
}

#chat-input {
    flex: 1;
    padding: 15px;
    border-radius: 25px;
    border: 1px solid rgba(164, 188, 46, 0.3);
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    font-size: 15px;
    resize: none;
    outline: none;
    font-family: inherit;
    transition: all 0.3s ease;
    max-height: 150px;
}

.light-theme #chat-input {
    background-color: rgba(234, 240, 220, 0.7);
    color: var(--color-text);
}

#chat-input:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
}

#send-button {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: var(--color-highlight);
    color: var(--color-text);
    border: none;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: all 0.3s ease;
}

.light-theme #send-button {
    color: white;
}

#send-button:hover {
    background-color: var(--color-accent3);
    transform: scale(1.05);
}

#send-button svg {
    width: 20px;
    height: 20px;
}


    </style>
</head>
<body class="dark-theme">
    <div class="dashboard">
        <header>
            <div class="logo">
                <h1>Chatbot & AI Training Logs</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </header>

        <div class="chat-container">
            <div class="chat-messages" id="chat-messages">
                <div class="welcome-message">
                    <h2>NutriSaur AI Assistant</h2>
                    <p>I'm your comprehensive NutriSaur system assistant! I can help you with:</p>
                    <ul style="text-align: left; display: inline-block; margin: 20px 0;">
                        <li>🔍 <b>Individual User Data:</b> "Tell me Kevin's risk score"</li>
                        <li>📍 <b>Location Analysis:</b> "What's the average risk score in Alion?"</li>
                        <li>📊 <b>System Analytics:</b> "How many SAM cases are there?"</li>
                        <li>🏥 <b>Health Insights:</b> "Show me malnutrition trends"</li>
                        <li>🍎 <b>Nutrition Advice:</b> Filipino culture-based dietary recommendations</li>
                        <li>💻 <b>System Information:</b> Database structure and API endpoints</li>
                    </ul>
                    <p><b>Ask me anything about the NutriSaur system or nutrition!</b></p>
                </div>
                <!-- Messages will appear here -->
            </div>
            
            <div class="chat-input-container">
                <textarea id="chat-input" placeholder="Type your message here..." rows="1"></textarea>
                <button id="send-button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="navbar">
        <div class="navbar-header">
            <div class="navbar-logo">
                <div class="navbar-logo-icon">
                    <img src="logo.png" alt="Logo" style="width: 40px; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash"><span class="navbar-icon"></span><span>Dashboard</span></a></li>

                <li><a href="event"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="ai"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v1.0 • © 2023</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>

    <script>
        // Remove the old theme toggle code and replace with this
        function loadSavedTheme() {
            const savedTheme = localStorage.getItem('nutrisaur-theme');
            if (savedTheme === 'light') {
                document.body.classList.remove('dark-theme');
                document.body.classList.add('light-theme');
                refreshChatColors();
            } else {
                document.body.classList.add('dark-theme');
                document.body.classList.remove('light-theme');
                refreshChatColors();
            }
        }

        // Keep the refreshChatColors function as it's specific to AI.html
        function refreshChatColors() {
            const isDarkTheme = document.body.classList.contains('dark-theme');
            
            // Update any dynamic color elements
            document.querySelectorAll('.message-bubble').forEach(bubble => {
                if (bubble.closest('.ai')) {
                    bubble.style.backgroundColor = isDarkTheme ? 
                        'rgba(42, 51, 38, 0.6)' : 
                        'rgba(142, 185, 110, 0.2)';
                }
            });
        }

        // Load theme on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadSavedTheme();
        });

        // Toggle chat expansion
        function toggleChat(element) {
            const chatItem = element.parentElement;
            const content = chatItem.querySelector('.chat-content');
            
            if (content.classList.contains('active')) {
                content.classList.remove('active');
            } else {
                // Close any open chats
                document.querySelectorAll('.chat-content.active').forEach(el => {
                    el.classList.remove('active');
                });
                content.classList.add('active');
            }
        }

        // Toggle training groups
        document.addEventListener('DOMContentLoaded', function() {
            const trainingHeaders = document.querySelectorAll('.training-group-header');
            trainingHeaders.forEach(header => {
                header.addEventListener('click', () => {
                    const group = header.parentElement;
                    group.classList.toggle('open');
                });
            });

            // Chat functionality
            const chatMessages = document.getElementById('chat-messages');
            const chatInput = document.getElementById('chat-input');
            const sendButton = document.getElementById('send-button');

            // Gemini API configuration
            const GEMINI_API_KEY = "AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM";
            const GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
            
            // Function to add a message to the chat
            function addMessage(text, isUser) {
                const message = document.createElement('div');
                message.className = isUser ? 'message user-message' : 'message ai-message';
                
                const avatar = document.createElement('div');
                avatar.className = 'message-avatar';
                avatar.textContent = isUser ? 'U' : 'AI';
                
                const content = document.createElement('div');
                content.className = 'message-content';
                
                // Use innerHTML for AI messages to render HTML formatting, but textContent for user messages
                if (isUser) {
                    content.textContent = text;
                } else {
                    content.innerHTML = text;
                }
                
                const time = document.createElement('div');
                time.className = 'message-time';
                const now = new Date();
                time.textContent = `${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;
                content.appendChild(time);
                
                message.appendChild(avatar);
                message.appendChild(content);
                
                chatMessages.appendChild(message);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Function to show typing animation
            function showTyping() {
                const typing = document.createElement('div');
                typing.className = 'message ai-message typing-message';
                typing.id = 'typing-indicator';
                
                const avatar = document.createElement('div');
                avatar.className = 'message-avatar';
                avatar.textContent = 'AI';
                
                const content = document.createElement('div');
                content.className = 'message-content message-typing';
                
                for (let i = 0; i < 3; i++) {
                    const dot = document.createElement('div');
                    dot.className = 'typing-dot';
                    content.appendChild(dot);
                }
                
                typing.appendChild(avatar);
                typing.appendChild(content);
                
                chatMessages.appendChild(typing);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Function to remove typing indicator
            function removeTyping() {
                const typing = document.getElementById('typing-indicator');
                if (typing) typing.remove();
            }
            
            // Function to handle AI response via API
            async function getAIResponse(userQuery) {
                showTyping();
                
                try {
                    // FIRST: Load system context and data structure
                    const systemContext = await loadSystemContext();
                    
                    // Check if user is asking about system information or user data
                    const lowerQuery = userQuery.toLowerCase();
                    
                    // Handle system information queries
                    if (lowerQuery.includes('system') || lowerQuery.includes('nutrisaur') || lowerQuery.includes('about')) {
                        const systemInfo = await getSystemInformation();
                        setTimeout(() => {
                            removeTyping();
                            addMessage(systemInfo, false);
                        }, 500);
                        return;
                    }
                    
                    // Handle comprehensive system data queries (check this first)
                    if (lowerQuery.includes('how many') || lowerQuery.includes('total') || lowerQuery.includes('count') || 
                        lowerQuery.includes('analytics') || lowerQuery.includes('statistics') || lowerQuery.includes('data') || 
                        lowerQuery.includes('dashboard') || lowerQuery.includes('users') || lowerQuery.includes('system') ||
                        lowerQuery.includes('edema') || lowerQuery.includes('malnutrition') || lowerQuery.includes('sam') ||
                        lowerQuery.includes('mam') || lowerQuery.includes('cases') || lowerQuery.includes('risk') ||
                        lowerQuery.includes('health') || lowerQuery.includes('screening')) {
                        
                        // Special handling for specific health condition queries
                        if (lowerQuery.includes('how many') && (lowerQuery.includes('edema') || lowerQuery.includes('swelling'))) {
                            const edemaData = await getSpecificHealthData('edema');
                            setTimeout(() => {
                                removeTyping();
                                addMessage(edemaData, false);
                            }, 500);
                            return;
                        }
                        
                        if (lowerQuery.includes('how many') && (lowerQuery.includes('malnutrition') || lowerQuery.includes('sam') || lowerQuery.includes('mam'))) {
                            const malnutritionData = await getSpecificHealthData('malnutrition');
                            setTimeout(() => {
                                removeTyping();
                                addMessage(malnutritionData, false);
                            }, 500);
                            return;
                        }
                        
                        // Get comprehensive system data for other queries
                        const comprehensiveData = await getComprehensiveSystemData();
                        setTimeout(() => {
                            removeTyping();
                            addMessage(comprehensiveData, false);
                        }, 500);
                        return;
                    }
                    
                    // Handle user risk score queries (personal data)
                    if (lowerQuery.includes('my risk') || lowerQuery.includes('my score') || lowerQuery.includes('my data') || 
                        lowerQuery.includes('personal') || lowerQuery.includes('profile')) {
                        const userData = await getUserData();
                        setTimeout(() => {
                            removeTyping();
                            addMessage(userData, false);
                        }, 500);
                        return;
                    }
                    
                    // Handle specific user queries (e.g., "tell me kevin risk score")
                    if (lowerQuery.includes('tell me') || lowerQuery.includes('what is') || lowerQuery.includes('show me')) {
                        const specificUserData = await getSpecificUserData(userQuery);
                        if (specificUserData) {
                            setTimeout(() => {
                                removeTyping();
                                addMessage(specificUserData, false);
                            }, 500);
                            return;
                        }
                    }
                    
                    // Handle location-specific queries (e.g., "avg risk score in alion")
                    if (lowerQuery.includes('in ') && (lowerQuery.includes('risk') || lowerQuery.includes('users') || lowerQuery.includes('data'))) {
                        const locationData = await getLocationSpecificData(userQuery);
                        if (locationData) {
                            setTimeout(() => {
                                removeTyping();
                                addMessage(locationData, false);
                            }, 500);
                            return;
                        }
                    }
                    
                    // Default nutrition advice using Gemini API with system context
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
                    
                    // Enhanced instructions for system-knowledgeable AI
                    const systemInstructions = `You are NutriSaur AI, a comprehensive system assistant and nutrition advisor.

SYSTEM CONTEXT:
${systemContext}

IMPORTANT RULES:
1. ALWAYS answer the specific question asked - no unnecessary information
2. Use the system data and table structure knowledge to provide accurate answers
3. If asked about specific users, locations, or data - provide exact information
4. If asked about system structure - explain based on the context provided
5. For nutrition advice - focus on Filipino culture and practical recommendations
6. Be concise and direct - no lengthy explanations unless specifically requested

User query: `;
                    
                    // Combine system instructions with user query
                    const promptWithSystemContext = systemInstructions + userQuery;
                    
                    // Use Gemini API for comprehensive system-aware responses
                    const response = await fetch(`${GEMINI_API_URL}?key=${GEMINI_API_KEY}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            contents: [{
                                parts: [{ text: promptWithSystemContext }]
                            }]
                        }),
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    let aiResponse;
                    
                    // Function to convert markdown to HTML (if API still returns markdown)
                    function markdownToHtml(text) {
                        // Convert markdown bold (**text** or *text*) to HTML bold
                        text = text.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
                        text = text.replace(/\*(.*?)\*/g, '<b>$1</b>');
                        
                        // Convert arrows
                        text = text.replace(/→/g, '→ ');
                        
                        // Convert newlines to <br> tags
                        text = text.replace(/\n/g, '<br>');
                        
                        return text;
                    }
                    
                    // Extract response from Gemini API format
                    if (data.candidates && data.candidates.length > 0 && 
                        data.candidates[0].content && 
                        data.candidates[0].content.parts && 
                        data.candidates[0].content.parts.length > 0) {
                        let responseText = data.candidates[0].content.parts[0].text;
                        // Process markdown to HTML
                        aiResponse = markdownToHtml(responseText);
                    } else {
                        aiResponse = "I couldn't generate a proper response. Please try again.";
                    }
                    
                    // Apply markdown to HTML conversion
                    aiResponse = markdownToHtml(aiResponse);
                    
                    setTimeout(() => {
                        removeTyping();
                        addMessage(aiResponse, false);
                    }, 500);
                    
                } catch (error) {
                    console.error('Error fetching from API:', error);
                    
                    setTimeout(() => {
                        removeTyping();
                        addMessage("Sorry, I couldn't connect to the AI service. Please try again later.", false);
                    }, 1000);
                }
            }
            
            // Function to get system information
            async function getSystemInformation() {
                try {
                    const response = await fetch('../unified_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'test_connection' })
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        return `<b>🌿 NutriSaur System Information</b><br><br>
                        <b>Status:</b> ✅ System Online<br>
                        <b>Database:</b> ${data.database}<br>
                        <b>Firebase:</b> ${data.firebase_config}<br>
                        <b>Server Time:</b> ${data.server_time}<br><br>
                        
                        <b>What I can help you with:</b><br>
                        • 📊 Check your nutrition risk scores<br>
                        • 🍎 Provide dietary advice and recommendations<br>
                        • 📈 Show system analytics and statistics<br>
                        • 🏥 Explain malnutrition screening results<br>
                        • 🥗 Suggest Filipino food options<br>
                        • 📱 Help with app features and usage<br><br>
                        
                        <b>Try asking:</b><br>
                        • "What's my risk score?"<br>
                        • "Show me system analytics"<br>
                        • "Give me nutrition advice"<br>
                        • "What is NutriSaur?"`;
                    } else {
                        return `<b>🌿 NutriSaur System Information</b><br><br>
                        <b>Status:</b> ⚠️ System Status Unknown<br><br>
                        <b>What I can help you with:</b><br>
                        • 📊 Check your nutrition risk scores<br>
                        • 🍎 Provide dietary advice and recommendations<br>
                        • 📈 Show system analytics and statistics<br>
                        • 🏥 Explain malnutrition screening results<br>
                        • 🥗 Suggest Filipino food options<br>
                        • 📱 Help with app features and usage<br><br>
                        
                        <b>Try asking:</b><br>
                        • "What's my risk score?"<br>
                        • "Show me system analytics"<br>
                        • "Give me nutrition advice"<br>
                        • "What is NutriSaur?"`;
                    }
                } catch (error) {
                    return `<b>🌿 NutriSaur System Information</b><br><br>
                    <b>Status:</b> ⚠️ Connection Error<br><br>
                    <b>What I can help you with:</b><br>
                    • 📊 Check your nutrition risk scores<br>
                    • 🍎 Provide dietary advice and recommendations<br>
                    • 📈 Show system analytics and statistics<br>
                    • 🏥 Explain malnutrition screening results<br>
                    • 🥗 Suggest Filipino food options<br>
                    • 📱 Help with app features and usage<br><br>
                    
                    <b>Try asking:</b><br>
                    • "What's my risk score?"<br>
                    • "Show me system analytics"<br>
                    • "Give me nutrition advice"<br>
                    • "What is NutriSaur?"`;
                }
            }
            
            // Function to get user data and risk score
            async function getUserData() {
                try {
                    // Get current user email from session or use a default for demo
                    const userEmail = '<?php echo htmlspecialchars($email); ?>';
                    
                    if (!userEmail || userEmail === '') {
                        return `<b>🔍 User Data Request</b><br><br>
                        ⚠️ <b>No user email found</b><br><br>
                        To check your personal data, please:<br>
                        1. Make sure you're logged in<br>
                        2. Try refreshing the page<br>
                        3. Contact support if the issue persists<br><br>
                        
                        <b>What I can still help with:</b><br>
                        • General nutrition advice<br>
                        • System information<br>
                        • Analytics overview`;
                    }
                    
                    const response = await fetch('../unified_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            action: 'get_screening_data',
                            email: userEmail
                        })
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        
                        if (data.success && data.data) {
                            const userData = data.data;
                            const riskScore = userData.risk_score || 'Not available';
                            const name = userData.name || 'Not provided';
                            const age = userData.age || 'Not available';
                            const gender = userData.gender || 'Not provided';
                            const barangay = userData.barangay || 'Not provided';
                            const income = userData.income || 'Not provided';
                            
                            let riskLevel = 'Unknown';
                            let riskColor = 'gray';
                            
                            if (riskScore !== 'Not available') {
                                if (riskScore >= 50) {
                                    riskLevel = 'High Risk';
                                    riskColor = 'red';
                                } else if (riskScore >= 30) {
                                    riskLevel = 'Moderate Risk';
                                    riskColor = 'orange';
                                } else if (riskScore >= 15) {
                                    riskLevel = 'Low Risk';
                                    riskColor = 'yellow';
                                } else {
                                    riskLevel = 'Very Low Risk';
                                    riskColor = 'green';
                                }
                            }
                            
                            return `<b>👤 Your Nutrition Profile</b><br><br>
                            <b>Name:</b> ${name}<br>
                            <b>Age:</b> ${age} months<br>
                            <b>Gender:</b> ${gender}<br>
                            <b>Location:</b> ${barangay}<br>
                            <b>Income Level:</b> ${income}<br><br>
                            
                            <b>Risk Assessment:</b><br>
                            <span style="color: ${riskColor}; font-weight: bold;">Risk Score: ${riskScore} - ${riskLevel}</span><br><br>
                            
                            <b>What this means:</b><br>
                            • <b>0-14:</b> Very Low Risk - Excellent nutrition status<br>
                            • <b>15-29:</b> Low Risk - Good nutrition, minor concerns<br>
                            • <b>30-49:</b> Moderate Risk - Some nutrition issues, monitor closely<br>
                            • <b>50+:</b> High Risk - Significant malnutrition risk, seek medical advice<br><br>
                            
                            <b>Recommendations:</b><br>
                            ${getRiskRecommendations(riskScore)}`;
                        } else {
                            return `<b>🔍 User Data Request</b><br><br>
                            ⚠️ <b>No screening data found</b><br><br>
                            It looks like you haven't completed a nutrition screening yet.<br><br>
                            
                            <b>To get your risk score:</b><br>
                            1. Complete the nutrition screening in the app<br>
                            2. Answer questions about your health and diet<br>
                            3. Provide measurements (height, weight, etc.)<br>
                            4. Submit the screening form<br><br>
                            
                            <b>What I can still help with:</b><br>
                            • General nutrition advice<br>
                            • System information<br>
                            • Analytics overview`;
                        }
                    } else {
                        return `<b>🔍 User Data Request</b><br><br>
                        ⚠️ <b>Error retrieving data</b><br><br>
                        Please try again later or contact support.<br><br>
                        
                        <b>What I can still help with:</b><br>
                        • General nutrition advice<br>
                        • System information<br>
                        • Analytics overview`;
                    }
                } catch (error) {
                    return `<b>🔍 User Data Request</b><br><br>
                    ⚠️ <b>Connection error</b><br><br>
                    Please check your internet connection and try again.<br><br>
                    
                    <b>What I can still help with:</b><br>
                    • General nutrition advice<br>
                    • System information<br>
                    • Analytics overview`;
                }
            }
            
            // Function to get comprehensive system data for detailed analysis
            async function getComprehensiveSystemData() {
                try {
                    const response = await fetch('../unified_api.php?type=dashboard', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        
                        if (data.total_users !== undefined) {
                            const totalUsers = data.total_users;
                            const usersWithRisk = data.users_with_risk || 0;
                            const averageRisk = data.average_risk || 0;
                            const atRiskUsers = data.at_risk_users || 0;
                            const samCases = data.sam_cases || 0;
                            const meanWHZ = data.mean_whz || 0;
                            const averageDDS = data.average_dds || 0;
                            
                            // Analyze critical health conditions from screening data
                            let criticalCases = [];
                            let moderateCases = [];
                            let lowRiskCases = [];
                            
                            if (data.preferences && Array.isArray(data.preferences)) {
                                data.preferences.forEach(pref => {
                                    if (pref.screening_answers) {
                                        try {
                                            const screeningData = JSON.parse(pref.screening_answers);
                                            
                                            // Check for critical conditions
                                            if (screeningData.swelling === 'yes') {
                                                criticalCases.push({
                                                    email: pref.user_email,
                                                    condition: 'Swelling detected',
                                                    priority: 'URGENT'
                                                });
                                            }
                                            
                                            if (screeningData.feeding_behavior === 'poor' || screeningData.feeding_behavior === 'poor appetite') {
                                                moderateCases.push({
                                                    email: pref.user_email,
                                                    condition: 'Poor feeding behavior',
                                                    priority: 'HIGH'
                                                });
                                            }
                                            
                                            if (screeningData.weight_loss === '>10%' || screeningData.weight_loss === 'yes') {
                                                moderateCases.push({
                                                    email: pref.user_email,
                                                    condition: 'Significant weight loss',
                                                    priority: 'HIGH'
                                                });
                                            }
                                            
                                            // Check for new clinical risk factors
                                            if (screeningData.has_recent_illness === true || screeningData.has_recent_illness === 'true') {
                                                moderateCases.push({
                                                    email: pref.user_email,
                                                    condition: 'Recent illness',
                                                    priority: 'MEDIUM'
                                                });
                                            }
                                            
                                            if (screeningData.has_eating_difficulty === true || screeningData.has_eating_difficulty === 'true') {
                                                moderateCases.push({
                                                    email: pref.user_email,
                                                    condition: 'Eating difficulty',
                                                    priority: 'MEDIUM'
                                                });
                                            }
                                            
                                            if (screeningData.has_food_insecurity === true || screeningData.has_food_insecurity === 'true') {
                                                moderateCases.push({
                                                    email: pref.user_email,
                                                    condition: 'Food insecurity',
                                                    priority: 'HIGH'
                                                });
                                            }
                                            
                                            if (screeningData.has_micronutrient_deficiency === true || screeningData.has_micronutrient_deficiency === 'true') {
                                                moderateCases.push({
                                                    email: pref.user_email,
                                                    condition: 'Micronutrient deficiency',
                                                    priority: 'MEDIUM'
                                                });
                                            }
                                            
                                            if (screeningData.has_functional_decline === true || screeningData.has_functional_decline === 'true') {
                                                moderateCases.push({
                                                    email: pref.user_email,
                                                    condition: 'Functional decline',
                                                    priority: 'MEDIUM'
                                                });
                                            }
                                            
                                        } catch (e) {
                                            // Skip invalid JSON
                                        }
                                    }
                                });
                            }
                            
                            // Generate detailed case reports
                            data.preferences.forEach(pref => {
                                if (pref.screening_answers) {
                                    try {
                                        const screeningData = JSON.parse(pref.screening_answers);
                                        
                                        // Check for critical conditions
                                        if (screeningData.swelling === 'yes') {
                                            criticalCases.push({
                                                email: pref.user_email,
                                                condition: 'Swelling detected',
                                                barangay: screeningData.barangay || 'Unknown',
                                                age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown'
                                            });
                                        }
                                        
                                        if (screeningData.feeding_behavior === 'poor' || screeningData.feeding_behavior === 'poor appetite') {
                                            moderateCases.push({
                                                email: pref.user_email,
                                                condition: 'Poor feeding behavior',
                                                barangay: screeningData.barangay || 'Unknown',
                                                age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown'
                                            });
                                        }
                                        
                                        // Check for new clinical risk factors
                                        if (screeningData.has_recent_illness === true || screeningData.has_recent_illness === 'true') {
                                            moderateCases.push({
                                                email: pref.user_email,
                                                condition: 'Recent illness',
                                                barangay: screeningData.barangay || 'Unknown',
                                                age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown'
                                            });
                                        }
                                        
                                        if (screeningData.has_eating_difficulty === true || screeningData.has_eating_difficulty === 'true') {
                                            moderateCases.push({
                                                email: pref.user_email,
                                                condition: 'Eating difficulty',
                                                barangay: screeningData.barangay || 'Unknown',
                                                age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown'
                                            });
                                        }
                                        
                                        if (screeningData.has_food_insecurity === true || screeningData.has_food_insecurity === 'true') {
                                            moderateCases.push({
                                                email: pref.user_email,
                                                condition: 'Food insecurity',
                                                barangay: screeningData.barangay || 'Unknown',
                                                age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown'
                                            });
                                        }
                                        
                                        if (screeningData.has_micronutrient_deficiency === true || screeningData.has_micronutrient_deficiency === 'true') {
                                            moderateCases.push({
                                                email: pref.user_email,
                                                condition: 'Micronutrient deficiency',
                                                barangay: screeningData.barangay || 'Unknown',
                                                age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown'
                                            });
                                        }
                                        
                                        if (screeningData.has_functional_decline === true || screeningData.has_functional_decline === 'true') {
                                            moderateCases.push({
                                                email: pref.user_email,
                                                condition: 'Functional decline',
                                                barangay: screeningData.barangay || 'Unknown',
                                                age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown'
                                            });
                                        }
                                        
                                    } catch (e) {
                                        console.error('Error parsing screening data:', e);
                                    }
                                }
                            });
                            
                            // Geographic distribution
                            let geographicInfo = '';
                            if (data.geographic_distribution && data.geographic_distribution.length > 0) {
                                geographicInfo = '<br><b>📍 Top Locations:</b><br>';
                                data.geographic_distribution.slice(0, 5).forEach(location => {
                                    geographicInfo += `• ${location.barangay}: ${location.total_users} users (${location.sam_cases} SAM cases)<br>`;
                                });
                            }
                            
                            // Age group analysis
                            let ageGroupInfo = '';
                            if (data.age_group_distribution) {
                                ageGroupInfo = '<br><b>👶 Age Group Distribution:</b><br>';
                                Object.entries(data.age_group_distribution).forEach(([group, info]) => {
                                    const groupName = group.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    ageGroupInfo += `• ${groupName}: ${info.total} users (${info.malnutrition} malnutrition cases)<br>`;
                                });
                            }
                            
                            // Gender breakdown
                            let genderInfo = '';
                            if (data.gender_breakdown) {
                                genderInfo = '<br><b>👥 Gender Breakdown:</b><br>';
                                Object.entries(data.gender_breakdown).forEach(([gender, info]) => {
                                    const genderName = gender.charAt(0).toUpperCase() + gender.slice(1);
                                    genderInfo += `• ${genderName}: ${info.total} users (${info.malnutrition} malnutrition cases)<br>`;
                                });
                            }
                            
                            return `<b>🏥 Comprehensive NutriSaur Health Analytics</b><br><br>
                            
                            <b>👥 User Statistics:</b><br>
                            • <b>Total Users:</b> ${totalUsers} users<br>
                            • <b>Users Screened:</b> ${usersWithRisk} users<br>
                            • <b>Average Risk Score:</b> ${averageRisk}<br>
                            • <b>At-Risk Users:</b> ${atRiskUsers} users<br><br>
                            
                            <b>🚨 Critical Health Conditions:</b><br>
                            • <b>Edema Cases:</b> ${criticalCases.length} users (bilateral swelling)<br>
                            • <b>Severe Malnutrition:</b> ${samCases} SAM cases<br>
                            • <b>Moderate+ Malnutrition:</b> ${moderateCases.length} users (risk ≥30)<br>
                            • <b>Poor Feeding:</b> ${moderateCases.filter(c => c.condition === 'Poor feeding behavior').length} users<br>
                            • <b>Significant Weight Loss:</b> ${moderateCases.filter(c => c.condition === 'Significant weight loss').length} users<br><br>
                            
                            <b>📊 Health Indicators:</b><br>
                            • <b>Mean WHZ:</b> ${meanWHZ} (Weight-for-Height Z-score)<br>
                            • <b>Average DDS:</b> ${averageDDS}/10 (Dietary Diversity)<br>
                            • <b>MUAC Data:</b> ${data.muac_data ? Object.values(data.muac_data).reduce((a, b) => a + b, 0) : 'N/A'} measurements<br><br>
                            
                            <b>📈 Risk Distribution:</b><br>
                            • <b>Very Low Risk (0-14):</b> ${data.risk_distribution ? data.risk_distribution[0] : 'N/A'} users<br>
                            • <b>Low Risk (15-29):</b> ${data.risk_distribution ? data.risk_distribution[1] : 'N/A'} users<br>
                            • <b>Moderate Risk (30-49):</b> ${data.risk_distribution ? data.risk_distribution[2] : 'N/A'} users<br>
                            • <b>High Risk (50+):</b> ${data.risk_distribution ? data.risk_distribution[3] : 'N/A'} users<br>${geographicInfo}${ageGroupInfo}${genderInfo}<br>
                            
                            <b>💻 System Health:</b><br>
                            • <b>Database:</b> ✅ Connected<br>
                            • <b>API Status:</b> ✅ Operational<br>
                            • <b>Last Updated:</b> ${new Date().toLocaleString()}<br><br>
                            
                            <b>💡 Key Insights:</b><br>
                            • <b>${criticalCases.length} users</b> have critical health conditions (immediate attention needed)<br>
                            • <b>${moderateCases.length} users</b> show moderate to severe malnutrition risk<br>
                            • <b>${samCases} users</b> meet SAM criteria (severe acute malnutrition)<br>
                            • <b>${moderateCases.filter(c => c.condition === 'Poor feeding behavior').length} users</b> report poor feeding behavior`;
                        } else {
                            return `<b>📊 Comprehensive System Analytics</b><br><br>
                            ⚠️ <b>Analytics data not available</b><br><br>
                            The system is currently gathering data.<br><br>
                            
                            <b>What I can still help with:</b><br>
                            • Check your personal risk score<br>
                            • Provide nutrition advice<br>
                            • Explain system features`;
                        }
                    } else {
                        return `<b>📊 Comprehensive System Analytics</b><br><br>
                        ⚠️ <b>Error retrieving analytics</b><br><br>
                        Please try again later.<br><br>
                        
                        <b>What I can still help with:</b><br>
                            • Check your personal risk score<br>
                            • Provide nutrition advice<br>
                            • Explain system features`;
                    }
                } catch (error) {
                    return `<b>📊 Comprehensive System Analytics</b><br><br>
                    ⚠️ <b>Connection error</b><br><br>
                    Please check your internet connection and try again.<br><br>
                    
                    <b>What I can still help with:</b><br>
                        • Check your personal risk score<br>
                        • Provide nutrition advice<br>
                        • Explain system features`;
                }
            }
            
            // NEW FUNCTION: Load comprehensive system context
            async function loadSystemContext() {
                try {
                    // Get system overview data
                    const dashboardResponse = await fetch('../unified_api.php?type=dashboard');
                    const dashboardData = await dashboardResponse.json();
                    
                    // Get table structure information
                    const tableStructure = await getTableStructure();
                    
                    // Build comprehensive system context
                    const context = `
NUTRISUR SYSTEM OVERVIEW:
NutriSaur is a comprehensive nutrition screening and health monitoring system designed for Filipino communities. It combines mobile app data collection with web-based analytics and AI-powered insights.

SYSTEM ARCHITECTURE:
- Database: MySQL with normalized structure
- API: RESTful unified_api.php serving multiple endpoints
- Frontend: Web dashboard with real-time analytics
- Mobile: Android app for data collection
- AI: Gemini-powered intelligent responses

CORE TABLES AND STRUCTURE:
${tableStructure}

CURRENT SYSTEM STATUS:
- Total Users: ${dashboardData.total_users || 'N/A'}
- Total Screened: ${dashboardData.total_screened || 'N/A'}
- SAM Cases: ${dashboardData.sam_cases || 'N/A'}
- Average Risk Score: ${dashboardData.average_risk || 'N/A'}
- Database Status: ✅ Connected
- API Status: ✅ Operational

AVAILABLE DATA ENDPOINTS:
- /unified_api.php?type=dashboard - Main dashboard data
- /unified_api.php?type=usm - User screening module data
- /unified_api.php?type=community_metrics - Community health metrics
- /unified_api.php?type=risk_distribution - Risk score distributions
- /unified_api.php?type=geographic_distribution - Location-based data
- /unified_api.php?type=critical_alerts - High-risk user alerts
- /unified_api.php?type=intelligent_programs - AI-generated intervention programs

DATA FIELDS AVAILABLE:
- User Demographics: name, email, birthday, age, gender, barangay, income
- Health Metrics: weight, height, BMI, MUAC, risk_score
- Screening Data: swelling, weight_loss, feeding_behavior, dietary_diversity
- Clinical Factors: recent_illness, eating_difficulty, food_insecurity, micronutrient_deficiency, functional_decline
- Physical Signs: physical_thin, physical_shorter, physical_weak, physical_none

LOCATION STRUCTURE:
- 12 Municipalities in Bataan Province
- Each municipality contains multiple barangays
- Data can be filtered by individual barangay or entire municipality
- Geographic distribution shows user concentration and health patterns

RISK ASSESSMENT SYSTEM:
- Risk scores calculated using MHO-approved formulas
- Categories: Very Low (0-14), Low (15-29), Moderate (30-49), High (50+)
- SAM criteria: Risk score ≥70 or MUAC <11.5cm or BMI <16
- MAM criteria: Risk score 30-49 or MUAC 11.5-12.5cm

AI CAPABILITIES:
- Real-time data analysis and insights
- Location-specific health assessments
- Individual user risk profiling
- Community health trend analysis
- Intelligent program recommendations
- Nutrition advice based on Filipino culture

QUERY EXAMPLES YOU CAN HANDLE:
- "Tell me Kevin's risk score" - Individual user data
- "What's the average risk score in Alion?" - Location-specific data
- "How many SAM cases are there?" - System-wide statistics
- "Show me users in Mariveles" - Geographic filtering
- "What's the malnutrition rate in children?" - Demographic analysis
`;

                    return context;
                    
                } catch (error) {
                    console.error('Error loading system context:', error);
                    return 'System context loading failed. Basic functionality available.';
                }
            }
            
            // NEW FUNCTION: Get table structure information
            async function getTableStructure() {
                try {
                    // Get sample data to understand structure
                    const response = await fetch('../unified_api.php?type=usm');
                    const data = await response.json();
                    
                    if (data.success && data.preferences && data.preferences.length > 0) {
                        const sampleUser = data.preferences[0];
                        const fields = Object.keys(sampleUser);
                        
                        let structure = 'MAIN TABLE: user_preferences\n';
                        structure += 'Key Fields:\n';
                        
                        // Categorize fields
                        const demographics = ['id', 'name', 'user_email', 'birthday', 'age', 'gender', 'barangay', 'income'];
                        const health = ['weight', 'height', 'bmi', 'muac', 'risk_score'];
                        const screening = ['swelling', 'weight_loss', 'feeding_behavior', 'dietary_diversity'];
                        const clinical = ['has_recent_illness', 'has_eating_difficulty', 'has_food_insecurity', 'has_micronutrient_deficiency', 'has_functional_decline'];
                        const physical = ['physical_thin', 'physical_shorter', 'physical_weak', 'physical_none'];
                        const other = ['allergies', 'diet_prefs', 'avoid_foods', 'created_at', 'updated_at'];
                        
                        structure += `• Demographics: ${demographics.join(', ')}\n`;
                        structure += `• Health Metrics: ${health.join(', ')}\n`;
                        structure += `• Screening Data: ${screening.join(', ')}\n`;
                        structure += `• Clinical Factors: ${clinical.join(', ')}\n`;
                        structure += `• Physical Signs: ${physical.join(', ')}\n`;
                        structure += `• Other: ${other.join(', ')}\n`;
                        
                        return structure;
                    } else {
                        return 'Table structure: user_preferences with screening data and health metrics';
                    }
                } catch (error) {
                    return 'Table structure: user_preferences table with comprehensive health data';
                }
            }
            
            // NEW FUNCTION: Get specific user data
            async function getSpecificUserData(query) {
                try {
                    // Extract name from query (e.g., "tell me kevin risk score")
                    const nameMatch = query.match(/tell me (\w+)/i) || query.match(/what is (\w+)/i) || query.match(/show me (\w+)/i);
                    if (!nameMatch) return null;
                    
                    const searchName = nameMatch[1].toLowerCase();
                    
                    // Get all users and search for matching name
                    const response = await fetch('../unified_api.php?type=usm');
                    const data = await response.json();
                    
                    if (data.success && data.preferences) {
                        const matchingUser = data.preferences.find(user => 
                            user.name && user.name.toLowerCase().includes(searchName) ||
                            user.username && user.username.toLowerCase().includes(searchName)
                        );
                        
                        if (matchingUser) {
                            const riskScore = matchingUser.risk_score || 'Not available';
                            const age = matchingUser.age || 'Not specified';
                            const gender = matchingUser.gender || 'Not specified';
                            const barangay = matchingUser.barangay || 'Not specified';
                            const bmi = matchingUser.bmi || 'Not available';
                            const muac = matchingUser.muac || 'Not available';
                            
                            let riskLevel = 'Unknown';
                            if (riskScore !== 'Not available') {
                                if (riskScore >= 50) riskLevel = 'High Risk';
                                else if (riskScore >= 30) riskLevel = 'Moderate Risk';
                                else if (riskScore >= 15) riskLevel = 'Low Risk';
                                else riskLevel = 'Very Low Risk';
                            }
                            
                            return `<b>👤 User Profile: ${matchingUser.name || matchingUser.username}</b><br><br>
                            <b>Risk Score:</b> ${riskScore} - ${riskLevel}<br>
                            <b>Age:</b> ${age} years<br>
                            <b>Gender:</b> ${gender}<br>
                            <b>Location:</b> ${barangay}<br>
                            <b>BMI:</b> ${bmi}<br>
                            <b>MUAC:</b> ${muac} cm<br><br>
                            
                            <b>Health Status:</b><br>
                            ${getHealthStatus(riskScore, bmi, muac)}`;
                        } else {
                            return `<b>🔍 User Search Result</b><br><br>
                            No user found with name containing "${searchName}".<br><br>
                            
                            <b>Available users:</b> ${data.preferences.length} total users in system<br>
                            <b>Try:</b> Check spelling or use email address instead`;
                        }
                    }
                    
                    return null;
                } catch (error) {
                    console.error('Error getting specific user data:', error);
                    return null;
                }
            }
            
            // NEW FUNCTION: Get location-specific data
            async function getLocationSpecificData(query) {
                try {
                    // Extract location from query (e.g., "avg risk score in alion")
                    const locationMatch = query.match(/in (\w+)/i);
                    if (!locationMatch) return null;
                    
                    const location = locationMatch[1];
                    
                    // Get community metrics for the specific location
                    const response = await fetch(`../unified_api.php?type=community_metrics&barangay=${location}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        const totalScreened = data.total_screened;
                        const highRiskCases = data.high_risk_cases;
                        const samCases = data.sam_cases;
                        
                        // Get risk distribution for the location
                        const riskResponse = await fetch(`../unified_api.php?type=risk_distribution&barangay=${location}`);
                        const riskData = await riskResponse.json();
                        
                        let riskBreakdown = '';
                        if (riskData.success && riskData.data.length > 0) {
                            riskBreakdown = '<br><b>Risk Distribution:</b><br>';
                            riskData.data.forEach(item => {
                                riskBreakdown += `• ${item.label}: ${item.value} users<br>`;
                            });
                        }
                        
                        return `<b>📍 Location Analysis: ${location}</b><br><br>
                        <b>Total Screened:</b> ${totalScreened} users<br>
                        <b>High Risk Cases:</b> ${highRiskCases} users<br>
                        <b>SAM Cases:</b> ${samCases} users<br>
                        <b>High Risk Rate:</b> ${totalScreened > 0 ? ((highRiskCases / totalScreened) * 100).toFixed(1) : 0}%${riskBreakdown}<br>
                        
                        <b>Health Status:</b><br>
                        ${getLocationHealthStatus(highRiskCases, samCases, totalScreened)}`;
                    }
                    
                    return null;
                } catch (error) {
                    console.error('Error getting location data:', error);
                    return null;
                }
            }
            
            // NEW FUNCTION: Get health status description
            function getHealthStatus(riskScore, bmi, muac) {
                if (riskScore === 'Not available') return 'Health status unavailable';
                
                if (riskScore >= 50) {
                    return '🚨 <b>High Risk</b> - Immediate medical attention recommended';
                } else if (riskScore >= 30) {
                    return '⚠️ <b>Moderate Risk</b> - Close monitoring and intervention needed';
                } else if (riskScore >= 15) {
                    return '🟡 <b>Low Risk</b> - Minor concerns, maintain healthy practices';
                } else {
                    return '✅ <b>Very Low Risk</b> - Excellent health status';
                }
            }
            
            // NEW FUNCTION: Get location health status
            function getLocationHealthStatus(highRiskCases, samCases, totalScreened) {
                if (totalScreened === 0) return 'No data available for this location';
                
                const highRiskRate = (highRiskCases / totalScreened) * 100;
                const samRate = (samCases / totalScreened) * 100;
                
                if (samRate > 5) {
                    return '🚨 <b>Critical</b> - High SAM rate requires emergency intervention';
                } else if (highRiskRate > 30) {
                    return '⚠️ <b>High Risk</b> - Significant malnutrition prevalence';
                } else if (highRiskRate > 15) {
                    return '🟡 <b>Moderate Risk</b> - Some concerns, monitoring needed';
                } else {
                    return '✅ <b>Healthy</b> - Good community health status';
                }
            }
            
            // Function to get analytics data (kept for backward compatibility)
            async function getAnalyticsData() {
                return await getComprehensiveSystemData();
            }
            
            // Function to get specific health condition data
            async function getSpecificHealthData(condition) {
                try {
                    const response = await fetch('../unified_api.php?type=dashboard', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        
                        if (data.preferences && Array.isArray(data.preferences)) {
                            let conditionCount = 0;
                            let conditionDetails = [];
                            let totalScreened = 0;
                            
                            data.preferences.forEach(pref => {
                                if (pref.screening_answers) {
                                    totalScreened++;
                                    try {
                                        const screeningData = JSON.parse(pref.screening_answers);
                                        
                                        if (condition === 'edema') {
                                            if (screeningData.swelling === 'yes') {
                                                conditionCount++;
                                                conditionDetails.push({
                                                    user: pref.user_email || 'Unknown',
                                                    risk_score: pref.risk_score || 'N/A',
                                                    barangay: screeningData.barangay || 'Unknown',
                                                    age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown'
                                                });
                                            }
                                        } else if (condition === 'malnutrition') {
                                            const riskScore = pref.risk_score || 0;
                                            if (riskScore >= 30) {
                                                conditionCount++;
                                                conditionDetails.push({
                                                    user: pref.user_email || 'Unknown',
                                                    risk_score: riskScore,
                                                    barangay: screeningData.barangay || 'Unknown',
                                                    age: screeningData.birthday ? calculateAgeFromBirthday(screeningData.birthday) : 'Unknown',
                                                    category: riskScore >= 50 ? 'SAM (Severe)' : 'MAM (Moderate)'
                                                });
                                            }
                                        }
                                    } catch (e) {
                                        // Skip invalid JSON
                                    }
                                }
                            });
                            
                            if (condition === 'edema') {
                                return `<b>🚨 Edema Cases Analysis</b><br><br>
                                
                                <b>📊 Summary:</b><br>
                                • <b>Total Edema Cases:</b> ${conditionCount} users<br>
                                • <b>Total Screened:</b> ${totalScreened} users<br>
                                • <b>Prevalence:</b> ${totalScreened > 0 ? ((conditionCount / totalScreened) * 100).toFixed(1) : 0}%<br><br>
                                
                                <b>⚠️ Critical Alert:</b><br>
                                Edema (bilateral swelling) indicates severe protein-energy malnutrition and requires <b>immediate medical attention</b>.<br><br>
                                
                                <b>👥 Affected Users:</b><br>
                                ${conditionDetails.length > 0 ? conditionDetails.slice(0, 5).map(detail => 
                                    `• ${detail.user} (Risk: ${detail.risk_score}, Location: ${detail.barangay}, Age: ${detail.age})`
                                ).join('<br>') : 'No detailed user information available'}`;
                            } else if (condition === 'malnutrition') {
                                return `<b>🍎 Malnutrition Cases Analysis</b><br><br>
                                
                                <b>📊 Summary:</b><br>
                                • <b>Total Malnutrition Cases:</b> ${conditionCount} users<br>
                                • <b>Total Screened:</b> ${totalScreened} users<br>
                                • <b>Prevalence:</b> ${totalScreened > 0 ? ((conditionCount / totalScreened) * 100).toFixed(1) : 0}%<br><br>
                                
                                <b>👥 Affected Users:</b><br>
                                ${conditionDetails.length > 0 ? conditionDetails.slice(0, 5).map(detail => 
                                    `• ${detail.user} (Risk: ${detail.risk_score}, Location: ${detail.barangay}, Age: ${detail.age}, Category: ${detail.category})`
                                ).join('<br>') : 'No detailed user information available'}`;
                            }
                        }
                    }
                    
                    return `<b>📊 Health Condition Analysis</b><br><br>
                    ⚠️ <b>Data not available</b><br><br>
                    Unable to retrieve ${condition} data at this time.`;
                    
                } catch (error) {
                    return `<b>📊 Health Condition Analysis</b><br><br>
                    ⚠️ <b>Error retrieving data</b><br><br>
                    Please try again later.`;
                }
            }
            
            // Helper function to calculate age from birthday
            function calculateAgeFromBirthday(birthday) {
                try {
                    if (!birthday) return 'Unknown';
                    const birthDate = new Date(birthday);
                    const today = new Date();
                    const ageInMonths = Math.floor((today - birthDate) / (1000 * 60 * 60 * 24 * 30.44));
                    return ageInMonths;
                } catch (error) {
                    return 'Unknown';
                }
            }
            
            // Function to get risk recommendations
            function getRiskRecommendations(riskScore) {
                if (riskScore === 'Not available') {
                    return 'Complete a nutrition screening to get personalized recommendations.';
                }
                
                const score = parseInt(riskScore);
                if (isNaN(score)) {
                    return 'Invalid risk score. Please complete a new screening.';
                }
                
                if (score >= 50) {
                    return '🚨 <b>Immediate medical attention required</b><br>• Consult healthcare provider immediately<br>• Therapeutic feeding program needed<br>• Regular monitoring and follow-up';
                } else if (score >= 30) {
                    return '⚠️ <b>Close monitoring and intervention needed</b><br>• Weekly health check-ups<br>• Nutritional supplementation<br>• Family counseling and support';
                } else if (score >= 15) {
                    return '🟡 <b>Minor concerns, maintain healthy practices</b><br>• Monthly health monitoring<br>• Balanced diet maintenance<br>• Regular exercise and activity';
                } else {
                    return '✅ <b>Excellent health status</b><br>• Continue healthy eating habits<br>• Regular health check-ups<br>• Share good practices with community';
                }
            }

// ... existing code ...