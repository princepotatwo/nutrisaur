<?php
// Community Users Database Viewer API
header('Content-Type: text/html; charset=UTF-8');

require_once '../config.php';

// Get data from database
try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get all community users
    $stmt = $pdo->prepare("SELECT * FROM community_users ORDER BY screening_date DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $totalUsers = count($users);
    $municipalities = array_unique(array_column($users, 'municipality'));
    $maleUsers = count(array_filter($users, function($user) { return strtolower($user['sex']) === 'male'; }));
    $femaleUsers = count(array_filter($users, function($user) { return strtolower($user['sex']) === 'female'; }));
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $users = [];
    $totalUsers = 0;
    $municipalities = [];
    $maleUsers = 0;
    $femaleUsers = 0;
}

function calculateAge($birthday) {
    $today = new DateTime();
    $birthDate = new DateTime($birthday);
    $age = $today->diff($birthDate)->y;
    return $age;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Users Database Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #a1b454 0%, #8ca86e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #a1b454;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #a1b454;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content {
            padding: 30px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .data-table th {
            background: #a1b454;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .data-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-male {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-female {
            background: #fce4ec;
            color: #c2185b;
        }

        .badge-pregnant {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .badge-not-pregnant {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-null {
            background: #f5f5f5;
            color: #666;
        }

        .municipality {
            font-weight: 600;
            color: #a1b454;
        }

        .barangay {
            color: #666;
            font-size: 0.9rem;
        }

        .email {
            color: #007bff;
            text-decoration: none;
        }

        .email:hover {
            text-decoration: underline;
        }

        .screening-date {
            color: #666;
            font-size: 0.9rem;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-data h3 {
            margin-bottom: 10px;
            color: #999;
        }

        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #a1b454;
            color: white;
        }

        .btn-primary:hover {
            background: #8ca86e;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .data-table {
                font-size: 0.85rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px 8px;
            }
            
            .controls {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Community Users Database</h1>
            <p>Nutritional Screening Data Viewer</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($municipalities); ?></div>
                <div class="stat-label">Municipalities</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $maleUsers; ?></div>
                <div class="stat-label">Male Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $femaleUsers; ?></div>
                <div class="stat-label">Female Users</div>
            </div>
        </div>

        <div class="content">
            <?php if (isset($error)): ?>
                <div class="error">
                    <h4>❌ Error Loading Data</h4>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="controls">
                <a href="/api/community_viewer.php" class="btn btn-primary">🔄 Refresh Data</a>
                <a href="/api/health.php" class="btn btn-secondary" target="_blank">🔗 API Health</a>
                <a href="/" class="btn btn-secondary">🏠 Back to Home</a>
            </div>

            <?php if (empty($users)): ?>
                <div class="no-data">
                    <h3>📭 No Data Available</h3>
                    <p>No community users found in the database.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Pregnancy</th>
                            <th>Measurements</th>
                            <th>Screening Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $age = calculateAge($user['birthday']);
                            $measurements = $user['weight'] . 'kg / ' . $user['height'] . 'cm / ' . $user['muac'] . 'cm';
                            $genderClass = strtolower($user['sex']) === 'male' ? 'badge-male' : 'badge-female';
                            
                            $pregnancy = '';
                            if ($user['is_pregnant'] === 'Yes') {
                                $pregnancy = '<span class="badge badge-pregnant">Yes</span>';
                            } elseif ($user['is_pregnant'] === 'No') {
                                $pregnancy = '<span class="badge badge-not-pregnant">No</span>';
                            } else {
                                $pregnancy = '<span class="badge badge-null">N/A</span>';
                            }
                            
                            $screeningDate = date('M j, Y g:i A', strtotime($user['screening_date']));
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="email"><?php echo htmlspecialchars($user['email']); ?></a></td>
                                <td>
                                    <div class="municipality"><?php echo htmlspecialchars($user['municipality']); ?></div>
                                    <div class="barangay"><?php echo htmlspecialchars($user['barangay']); ?></div>
                                </td>
                                <td><span class="badge <?php echo $genderClass; ?>"><?php echo htmlspecialchars($user['sex']); ?></span></td>
                                <td><?php echo $age; ?> years</td>
                                <td><?php echo $pregnancy; ?></td>
                                <td><small><?php echo htmlspecialchars($measurements); ?></small></td>
                                <td><span class="screening-date"><?php echo $screeningDate; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
