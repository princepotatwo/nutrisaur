<?php
session_start();

// Use Universal DatabaseAPI - NO MORE HARDCODED CONNECTIONS!
require_once __DIR__ . '/api/DatabaseHelper.php';

// Get database helper instance
$db = DatabaseHelper::getInstance();

// Check if database is available
if (!$db->isAvailable()) {
    die('Database connection failed. Please check your configuration.');
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    
    try {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'get_users':
                // Simple get users from community_users
                $result = $db->select('community_users', '*', '', [], 'screening_date DESC');
                
                if ($result['success']) {
                    $response['success'] = true;
                    $response['data'] = $result['data'];
                    $response['message'] = 'Users retrieved successfully';
                } else {
                    throw new Exception($result['message']);
                }
                break;
                
            case 'delete_user':
                if (empty($_POST['email'])) {
                    throw new Exception('Email is required');
                }
                
                $email = $_POST['email'];
                $result = $db->delete('community_users', 'email = ?', [$email]);
                
                if ($result['success'] && $result['affected_rows'] > 0) {
                    $response['success'] = true;
                    $response['message'] = 'User deleted successfully';
                } else {
                    $response['message'] = 'User not found';
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Nutrisaur</title>
<style>
:root {
            --color-primary: #a1b454;
            --color-secondary: #546048;
            --color-accent: #8a9a3a;
            --color-accent2: #6b7c32;
            --color-accent3: #4a5a2a;
            --color-text: #2c3e50;
            --color-text-light: #7f8c8d;
            --color-background: #f8f9fa;
            --color-card: #ffffff;
            --color-border: #e9ecef;
            --color-hover: #f1f3f4;
    --color-shadow: rgba(0, 0, 0, 0.1);
            --color-success: #28a745;
            --color-warning: #ffc107;
            --color-danger: #dc3545;
            --color-info: #17a2b8;
            --color-highlight: #a1b454;
        }

        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
        }

        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--color-text);
    line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-secondary) 100%);
            color: white;
            padding: 20px 0;
    margin-bottom: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            text-align: center;
            font-size: 2.5rem;
    font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .nav-bar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 15px;
    margin-top: 20px;
}

        .nav-links {
    display: flex;
        justify-content: center;
            gap: 30px;
        flex-wrap: wrap;
    }
    
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 12px 24px;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

        .nav-links a.active {
            background: rgba(255, 255, 255, 0.3);
    font-weight: 600;
        }

        .content-section {
            background: var(--color-card);
    border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.8rem;
            color: var(--color-primary);
            margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
        }

        .stats-grid {
    display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-accent) 100%);
    color: white;
    padding: 25px;
            border-radius: 12px;
    text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 1.1rem;
    margin-bottom: 10px;
    opacity: 0.9;
}

        .stat-card p {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--color-text-light);
    font-style: italic;
}

        .error {
            text-align: center;
    padding: 20px;
            color: var(--color-danger);
            background: rgba(220, 53, 69, 0.1);
            border-radius: 8px;
            margin: 20px 0;
        }

        .success {
    text-align: center;
            padding: 20px;
            color: var(--color-success);
            background: rgba(40, 167, 69, 0.1);
    border-radius: 8px;
            margin: 20px 0;
        }

        /* Table styling */
        #users-table tbody tr:hover {
            background-color: var(--color-hover);
            transition: background-color 0.3s ease;
        }

        #users-table tbody tr:nth-child(even) {
            background-color: rgba(161, 180, 84, 0.05);
        }

        #users-table tbody tr:nth-child(odd) {
            background-color: rgba(161, 180, 84, 0.02);
        }

        #users-table tbody tr:hover {
    background-color: rgba(161, 180, 84, 0.1);
        }

        /* Button hover effects */
        button:hover {
            opacity: 0.9;
        transform: translateY(-1px);
    transition: all 0.3s ease;
}

        /* Search input focus */
        #search-input:focus {
    outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.1);
        }

        @media (max-width: 768px) {
            .container {
    padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .nav-links {
                gap: 15px;
            }
            
            .nav-links a {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }

            #users-table {
                min-width: 800px;
            }

            .content-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h1>Settings</h1>
            <div class="nav-bar">
                <div class="nav-links">
                    <a href="home.php">Home</a>
                    <a href="screening.php">Screening</a>
                    <a href="settings.php" class="active">Settings</a>
                    <a href="data_dashboard.html">Data Dashboard</a>
                </div>
        </div>
    </div>

        <!-- Stats Section -->
        <div class="content-section">
            <h2 class="section-title">Community Users Overview</h2>
            <div class="stats-grid" id="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p id="total-users">Loading...</p>
            </div>
                <div class="stat-card">
                    <h3>Municipalities</h3>
                    <p id="total-municipalities">Loading...</p>
            </div>
                <div class="stat-card">
                    <h3>Male Users</h3>
                    <p id="male-users">Loading...</p>
                    </div>
                <div class="stat-card">
                    <h3>Female Users</h3>
                    <p id="female-users">Loading...</p>
                        </div>
                    </div>
        </div>

        <!-- Loading Message -->
        <div id="loading" class="loading" style="display: none;">
            Loading community users data...
            </div>
            
        <!-- Error Message -->
        <div id="error" class="error" style="display: none;"></div>

        <!-- Success Message -->
        <div id="success" class="success" style="display: none;"></div>

        <!-- Users Table Section -->
        <div class="content-section">
            <h2 class="section-title">Community Users Management</h2>
            
            <!-- Controls -->
            <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center;">
                <button onclick="loadUsers()" style="padding: 10px 20px; background: var(--color-primary); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    Refresh Data
                </button>
                <input type="text" id="search-input" placeholder="Search by name, email, or location..." 
                       style="flex: 1; min-width: 200px; padding: 10px; border: 2px solid var(--color-border); border-radius: 8px; font-size: 14px;">
            </div>
            
            <!-- Table Container -->
            <div style="overflow-x: auto; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);">
                <table id="users-table" style="width: 100%; border-collapse: collapse; min-width: 1000px;">
                    <thead style="background: var(--color-primary); color: white;">
                        <tr>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Name</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Email</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Municipality</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Barangay</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Sex</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Birthday</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Pregnant</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Weight</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Height</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">MUAC</th>
                            <th style="padding: 15px 10px; text-align: left; font-weight: 600;">Screening Date</th>
                            <th style="padding: 15px 10px; text-align: center; font-weight: 600;">Actions</th>
                    </tr>
                </thead>
                    <tbody id="users-tbody">
                        <tr>
                            <td colspan="12" style="text-align: center; padding: 40px; color: var(--color-text-light); font-style: italic;">
                                Click "Refresh Data" to load users
                            </td>
                        </tr>
                </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let allUsers = [];
        let filteredUsers = [];

        // Load stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            setupSearch();
        });

        async function loadStats() {
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const statsGrid = document.getElementById('stats-grid');
            
            loading.style.display = 'block';
            error.style.display = 'none';
            statsGrid.style.display = 'none';

            try {
                const response = await fetch('settings_new.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                    body: 'action=get_users'
                });

                const data = await response.json();

                if (data.success) {
                    updateStats(data.data);
                    statsGrid.style.display = 'grid';
            } else {
                    throw new Error(data.message || 'Failed to load data');
                }
            } catch (err) {
                console.error('Error loading stats:', err);
                error.textContent = 'Error: ' + err.message;
                error.style.display = 'block';
            } finally {
                loading.style.display = 'none';
            }
        }

        function updateStats(users) {
            const totalUsers = users.length;
            const municipalities = [...new Set(users.map(user => user.municipality))].length;
            const maleUsers = users.filter(user => user.sex && user.sex.toLowerCase() === 'male').length;
            const femaleUsers = users.filter(user => user.sex && user.sex.toLowerCase() === 'female').length;

            document.getElementById('total-users').textContent = totalUsers;
            document.getElementById('total-municipalities').textContent = municipalities;
            document.getElementById('male-users').textContent = maleUsers;
            document.getElementById('female-users').textContent = femaleUsers;
        }

        // Load users data
        async function loadUsers() {
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const tbody = document.getElementById('users-tbody');
            
            loading.style.display = 'block';
            error.style.display = 'none';

            try {
                const response = await fetch('settings_new.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_users'
                });

                const data = await response.json();

                if (data.success) {
                    allUsers = data.data;
                    filteredUsers = [...allUsers];
                    displayUsers(filteredUsers);
                            } else {
                    throw new Error(data.message || 'Failed to load users');
                }
            } catch (err) {
                console.error('Error loading users:', err);
                error.textContent = 'Error: ' + err.message;
                error.style.display = 'block';
                tbody.innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 40px; color: var(--color-danger);">Error loading users</td></tr>';
            } finally {
                loading.style.display = 'none';
            }
        }

        // Display users in table
        function displayUsers(users) {
            const tbody = document.getElementById('users-tbody');
        
        if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" style="text-align: center; padding: 40px; color: var(--color-text-light); font-style: italic;">No users found</td></tr>';
            return;
        }
        
            tbody.innerHTML = '';
        users.forEach(user => {
            const row = document.createElement('tr');
                row.style.borderBottom = '1px solid var(--color-border)';
            row.innerHTML = `
                    <td style="padding: 12px 10px;">${user.name || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.email || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.municipality || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.barangay || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.sex || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.birthday || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.is_pregnant || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.weight || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.height || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.muac || 'N/A'}</td>
                    <td style="padding: 12px 10px;">${user.screening_date || 'N/A'}</td>
                    <td style="padding: 12px 10px; text-align: center;">
                        <button onclick="deleteUser('${user.email}')" 
                                style="padding: 6px 12px; background: var(--color-danger); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; margin: 0 2px;">
                            Delete
                        </button>
                </td>
            `;
                tbody.appendChild(row);
            });
        }

        // Delete user function
    async function deleteUser(email) {
            if (!confirm(`Are you sure you want to delete user: ${email}?`)) {
            return;
        }

            try {
                const response = await fetch('settings_new.php', {
                method: 'POST',
                headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                },
                    body: `action=delete_user&email=${encodeURIComponent(email)}`
            });
            
            const data = await response.json();

                if (data.success) {
                    showMessage('success', data.message);
                    loadUsers(); // Refresh the table
                    loadStats(); // Refresh stats
            } else {
                    showMessage('error', data.message);
                }
            } catch (err) {
                console.error('Error deleting user:', err);
                showMessage('error', 'Error deleting user: ' + err.message);
            }
        }

        // Setup search functionality
        function setupSearch() {
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                filteredUsers = allUsers.filter(user => 
                    (user.name && user.name.toLowerCase().includes(searchTerm)) ||
                    (user.email && user.email.toLowerCase().includes(searchTerm)) ||
                    (user.municipality && user.municipality.toLowerCase().includes(searchTerm)) ||
                    (user.barangay && user.barangay.toLowerCase().includes(searchTerm))
                );
                displayUsers(filteredUsers);
            });
        }

        // Show message function
        function showMessage(type, message) {
            const error = document.getElementById('error');
            const success = document.getElementById('success');
            
            error.style.display = 'none';
            success.style.display = 'none';
            
            if (type === 'error') {
                error.textContent = message;
                error.style.display = 'block';
            } else if (type === 'success') {
                success.textContent = message;
                success.style.display = 'block';
            }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
                error.style.display = 'none';
                success.style.display = 'none';
        }, 5000);
    }
    </script>
</body>
</html>
                                                                        