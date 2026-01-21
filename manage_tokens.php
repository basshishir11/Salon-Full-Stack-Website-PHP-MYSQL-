<?php
// pages/admin/manage_tokens.php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../config/database.php';
require_once '../../includes/csrf.php';
startBookingSession();

$database = new Database();
$db = $database->getConnection();

// Get tokens for the selected date (default today)
$date = $_GET['date'] ?? date('Y-m-d');
$query = "SELECT t.*, c.name as customer_name, c.phone 
          FROM tokens t 
          JOIN customers c ON t.customer_id = c.id 
          WHERE (t.appointment_date = :date OR (t.appointment_date IS NULL AND DATE(t.created_at) = :date))
          ORDER BY t.appointment_time ASC, t.id ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(':date', $date);
$stmt->execute();
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate by gender
$boys_tokens = array_filter($tokens, fn($t) => $t['gender'] === 'Men');
$girls_tokens = array_filter($tokens, fn($t) => $t['gender'] === 'Women');

function countByStatus($tokenList, $statusGroup) {
    return count(array_filter($tokenList, function($t) use ($statusGroup) {
        if ($statusGroup === 'Pending') return in_array($t['status'], ['Waiting', 'In Service']);
        return $t['status'] === $statusGroup;
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tokens</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-nav {
            background: var(--dark-gray);
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .admin-nav a { color: #fff; margin-left: 15px; text-decoration: underline; }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            border-bottom: 2px solid #eee;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        /* Sub-tabs styling */
        .sub-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 12px;
        }
        .sub-tab {
            flex: 1;
            padding: 8px 12px;
            border: none;
            background: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }
        .sub-tab span {
            background: #eee;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 11px;
        }
        .sub-tab.active {
            background: var(--primary-color);
            color: white;
        }
        .sub-tab.active span {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        .token-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #ddd;
        }
        .token-card.waiting { border-left-color: var(--warning-color); }
        .token-card.in-service { border-left-color: var(--primary-color); }
        .token-card.completed { border-left-color: var(--success-color); }
        .token-card.cancelled { border-left-color: var(--danger-color); opacity: 0.6; }
        
        .token-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .token-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-waiting { background: #fff3cd; color: #856404; }
        .status-in-service { background: #e0e7ff; color: #4338ca; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .token-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
            min-height: auto;
            width: auto;
            border-radius: 5px;
        }
        .btn-call { background: var(--primary-color); color: white; }
        .btn-complete { background: var(--success-color); color: white; }
        .btn-cancel { background: var(--danger-color); color: white; }
    </style>
</head>
<body>

<div class="admin-nav">
    <div>
        <strong>Manage Tokens</strong>
        <span id="liveStatus" style="display: none; margin-left: 15px; font-size: 12px; padding: 4px 10px; border-radius: 12px; background: #ddd; color: #666;">
            <i class="fas fa-circle"></i> Connecting...
        </span>
    </div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_rewards.php" style="color: #d97706; font-weight: 600;"><i class="fas fa-gift"></i> Rewards</a>
        <a href="../../ajax/logout.php" onclick="event.preventDefault(); fetch('../../ajax/logout.php').then(() => window.location.href='login.php');">Logout</a>
    </div>
</div>

<div class="container admin-container" style="max-width: 1200px; padding: 20px; background: transparent;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Token Management</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="date" id="dateFilter" value="<?php echo $date; ?>" class="form-control" style="width: auto; padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
            <a href="manage_tokens.php" class="btn btn-secondary btn-sm" style="padding: 5px 10px; font-size: 13px;">Today</a>
        </div>
    </div>

    <!-- Main Gender Tabs -->
    <div class="tabs">
        <button class="tab active" onclick="switchMainTab('boys')">
            Boys Queue
        </button>
        <button class="tab" onclick="switchMainTab('girls')">
            Girls Queue
        </button>
    </div>

    <!-- Boys Section -->
    <div id="boys-section" class="tab-content active">
        <div class="sub-tabs">
            <button class="sub-tab active" onclick="switchStatusTab('boys', 'Pending')">
                Pending <span><?php echo countByStatus($boys_tokens, 'Pending'); ?></span>
            </button>
            <button class="sub-tab" onclick="switchStatusTab('boys', 'Completed')">
                Completed <span><?php echo countByStatus($boys_tokens, 'Completed'); ?></span>
            </button>
            <button class="sub-tab" onclick="switchStatusTab('boys', 'Cancelled')">
                Cancelled <span><?php echo countByStatus($boys_tokens, 'Cancelled'); ?></span>
            </button>
        </div>

        <div id="boys-tokens-container">
            <?php if (empty($boys_tokens)): ?>
                <p class="text-center no-tokens-msg" style="color: #666;">No tokens for boys on this date</p>
            <?php else: ?>
                <?php foreach ($boys_tokens as $token): ?>
                    <?php renderTokenCard($token); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Girls Section -->
    <div id="girls-section" class="tab-content">
        <div class="sub-tabs">
            <button class="sub-tab active" onclick="switchStatusTab('girls', 'Pending')">
                Pending <span><?php echo countByStatus($girls_tokens, 'Pending'); ?></span>
            </button>
            <button class="sub-tab" onclick="switchStatusTab('girls', 'Completed')">
                Completed <span><?php echo countByStatus($girls_tokens, 'Completed'); ?></span>
            </button>
            <button class="sub-tab" onclick="switchStatusTab('girls', 'Cancelled')">
                Cancelled <span><?php echo countByStatus($girls_tokens, 'Cancelled'); ?></span>
            </button>
        </div>

        <div id="girls-tokens-container">
            <?php if (empty($girls_tokens)): ?>
                <p class="text-center no-tokens-msg" style="color: #666;">No tokens for girls on this date</p>
            <?php else: ?>
                <?php foreach ($girls_tokens as $token): ?>
                    <?php renderTokenCard($token); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// State to track current filters
const currentFilters = {
    main: 'boys',
    boysStatus: 'Pending',
    girlsStatus: 'Pending'
};

document.getElementById('dateFilter').addEventListener('change', function() {
    window.location.href = 'manage_tokens.php?date=' + this.value;
});

function switchMainTab(gender) {
    currentFilters.main = gender;
    
    // Update main tab UI
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    // Show/hide sections
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(gender + '-section').classList.add('active');
    
    applyFilters();
}

function switchStatusTab(gender, status) {
    currentFilters[gender + 'Status'] = status;
    
    // Update sub-tab UI within the correct section
    const section = document.getElementById(gender + '-section');
    section.querySelectorAll('.sub-tab').forEach(st => st.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    applyFilters();
}

function applyFilters() {
    ['boys', 'girls'].forEach(gender => {
        const container = document.getElementById(gender + '-tokens-container');
        if (!container) return;
        
        const status = currentFilters[gender + 'Status'];
        const cards = container.querySelectorAll('.token-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const cardStatus = card.dataset.status;
            let show = false;
            
            if (status === 'Pending') {
                show = (cardStatus === 'Waiting' || cardStatus === 'In Service');
            } else {
                show = (cardStatus === status);
            }
            
            card.style.display = show ? 'block' : 'none';
            if (show) visibleCount++;
        });
        
        // Show "No tokens" message if none are visible
        let noMsg = container.querySelector('.filter-empty-msg');
        if (visibleCount === 0 && cards.length > 0) {
            if (!noMsg) {
                noMsg = document.createElement('p');
                noMsg.className = 'text-center filter-empty-msg';
                noMsg.style.color = '#666';
                noMsg.style.padding = '20px';
                noMsg.textContent = `No ${status.toLowerCase()} tokens found.`;
                container.appendChild(noMsg);
            }
        } else if (noMsg) {
            noMsg.remove();
        }
    });
}

function updateTokenStatus(tokenId, status) {
    const formData = new FormData();
    formData.append('token_id', tokenId);
    formData.append('status', status);

    fetch('../../ajax/update_token.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Update failed');
        }
    });
}

// SSE for live updates
function connectSSE() {
    if (eventSource) eventSource.close();
    
    eventSource = new EventSource('../../ajax/sse_queue.php');
    
// State to track last known data to prevent unnecessary reloads
let lastDataFingerprint = null;

eventSource.onmessage = function(event) {
    const data = JSON.parse(event.data);
    
    // Only process 'update' messages for change detection
    if (data.type !== 'update') return;

    // Create a fingerprint of the current state (counts and statuses)
    const currentFingerprint = JSON.stringify({
        bc: data.boys_count,
        gc: data.girls_count,
        bt: data.boys?.map(t => `${t.id}-${t.status}`),
        gt: data.girls?.map(t => `${t.id}-${t.status}`)
    });

    if (lastDataFingerprint !== null && lastDataFingerprint !== currentFingerprint) {
        // Data has changed! Reload to update UI
        if (!window.isReloading) {
            window.isReloading = true;
            location.reload();
        }
    }
    
    lastDataFingerprint = currentFingerprint;
};
    
    eventSource.onopen = function() {
        const statusEl = document.getElementById('liveStatus');
        statusEl.innerHTML = '<i class="fas fa-circle" style="color: #28a745;"></i> Live Updates Active';
        statusEl.style.background = '#d1fae5';
        statusEl.style.color = '#065f46';
    };
    
    eventSource.onerror = function() {
        const statusEl = document.getElementById('liveStatus');
        statusEl.innerHTML = '<i class="fas fa-circle" style="color: #dc3545;"></i> Disconnected';
        statusEl.style.background = '#f8d7da';
        statusEl.style.color = '#842029';
        
        // Try to reconnect after 5 seconds
        setTimeout(() => {
            if (eventSource) {
                eventSource.close();
                connectSSE();
            }
        }, 5000);
    };
}

let eventSource;
document.addEventListener('DOMContentLoaded', () => {
    connectSSE();
    applyFilters(); // Initial filter application
});

window.addEventListener('beforeunload', () => {
    if (eventSource) eventSource.close();
});
</script>

</body>
</html>

<?php
function renderTokenCard($token) {
    $statusClass = strtolower(str_replace(' ', '-', $token['status']));
    ?>
    <div class="token-card <?php echo $statusClass; ?>" data-status="<?php echo htmlspecialchars($token['status']); ?>">
        <div class="token-header">
            <div>
                <span class="token-number"><?php echo $token['token_number']; ?></span>
                <?php if (!empty($token['appointment_time'])): ?>
                    <span style="font-size: 14px; color: #666; margin-left: 10px; font-weight: 500;">
                        <i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($token['appointment_time'])); ?>
                    </span>
                <?php endif; ?>
            </div>
            <span class="status-badge status-<?php echo $statusClass; ?>">
                <?php echo htmlspecialchars($token['status']); ?>
            </span>
        </div>
        <div style="font-size: 14px; color: #666;">
            <div><strong><?php echo htmlspecialchars($token['customer_name']); ?></strong></div>
            <div>Phone: <?php echo htmlspecialchars($token['phone']); ?></div>
            <div>Services: <?php echo htmlspecialchars($token['services_summary']); ?></div>
            <div>Booked on: <?php echo date('M j, h:i A', strtotime($token['created_at'])); ?></div>
        </div>
        
        <?php if ($token['status'] === 'Waiting'): ?>
            <div class="token-actions">
                <button class="btn btn-sm btn-call" onclick="updateTokenStatus(<?php echo $token['id']; ?>, 'In Service')">
                    <i class="fas fa-phone"></i> Call
                </button>
                <button class="btn btn-sm btn-complete" onclick="updateTokenStatus(<?php echo $token['id']; ?>, 'Completed')">
                    <i class="fas fa-check"></i> Complete
                </button>
                <button class="btn btn-sm btn-cancel" onclick="updateTokenStatus(<?php echo $token['id']; ?>, 'Cancelled')">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        <?php elseif ($token['status'] === 'In Service'): ?>
            <div class="token-actions">
                <button class="btn btn-sm btn-complete" onclick="updateTokenStatus(<?php echo $token['id']; ?>, 'Completed')">
                    <i class="fas fa-check"></i> Complete
                </button>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
