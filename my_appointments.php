<?php
// pages/my_appointments.php
require_once '../includes/csrf.php';
startBookingSession();
require_once '../includes/header.php';
require_once '../config/database.php';

$isLoggedIn = isset($_SESSION['customer_id']);
$appointments = [];

if ($isLoggedIn) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Fetch customer info for loyalty & referral
    $cust_query = "SELECT visit_count, referral_code FROM customers WHERE id = :cid";
    $cust_stmt = $db->prepare($cust_query);
    $cust_stmt->execute([':cid' => $_SESSION['customer_id']]);
    $customer_info = $cust_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure referral code exists (self-healing)
    if (empty($customer_info['referral_code'])) {
        $ref_code = 'SALON-' . strtoupper(substr(md5(uniqid($_SESSION['customer_phone'], true)), 0, 6));
        $db->prepare("UPDATE customers SET referral_code = ? WHERE id = ?")->execute([$ref_code, $_SESSION['customer_id']]);
        $customer_info['referral_code'] = $ref_code;
    }

    // Fetch earned rewards
    $rew_query = "SELECT * FROM rewards WHERE customer_id = :cid ORDER BY created_at DESC";
    $rew_stmt = $db->prepare($rew_query);
    $rew_stmt->execute([':cid' => $_SESSION['customer_id']]);
    $rewards = $rew_stmt->fetchAll(PDO::FETCH_ASSOC);

    $query = "SELECT t.* 
              FROM tokens t 
              WHERE t.customer_id = :cid 
              AND t.status IN ('Waiting', 'In Service')
              ORDER BY t.appointment_date ASC, t.appointment_time ASC, t.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([':cid' => $_SESSION['customer_id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.9);
        --glass-border: rgba(255, 255, 255, 0.2);
        --premium-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }

    body {
        background: radial-gradient(circle at top right, #f8fafc 0%, #e2e8f0 100%) !important;
    }

    .portal-container {
        padding: 40px 20px;
        max-width: 500px;
        margin: 0 auto;
    }

    .login-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: 24px;
        padding: 40px 30px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        text-align: center;
    }

    .login-header h2 {
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
    }

    .login-header p {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 30px;
    }

    .form-control-premium {
        background: #f1f5f9;
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 14px 20px;
        font-size: 1rem;
        transition: all 0.2s;
        width: 100%;
        margin-bottom: 20px;
        outline: none;
    }

    .form-control-premium:focus {
        background: white;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(142, 118, 255, 0.1);
    }

    .loyalty-premium {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        border-radius: 24px;
        padding: 24px;
        color: white;
        box-shadow: 0 12px 24px rgba(79, 70, 229, 0.2);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .loyalty-premium::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }

    .progress-track {
        height: 8px;
        background: rgba(255,255,255,0.2);
        border-radius: 10px;
        margin: 20px 0 10px;
        position: relative;
    }

    .progress-fill {
        height: 100%;
        background: #fbbf24;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(251, 191, 36, 0.4);
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .milestone-dots {
        display: flex;
        justify-content: space-between;
        margin-top: -14px;
    }

    .dot {
        width: 12px;
        height: 12px;
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        border: 2px solid #7c3aed;
        z-index: 2;
    }

    .dot.active {
        background: #fbbf24;
        border-color: white;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .premium-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: var(--premium-shadow);
        border: 1px solid #f1f5f9;
        margin-bottom: 16px;
        transition: transform 0.2s;
    }

    .premium-card:hover {
        transform: translateY(-2px);
    }

    .reward-badge {
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 8px;
    }

    .status-waiting { background: #fef9c3; color: #854d0e; }
    .status-in-service { background: #dcfce7; color: #166534; }

    .token-number {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--primary-color);
        line-height: 1;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #64748b;
        font-size: 0.9rem;
        margin-top: 6px;
    }
</style>

<div class="portal-container">
    
    <?php if (!$isLoggedIn): ?>
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Enter your phone number to access your appointments and rewards.</p>
            </div>
            
            <form id="loginForm">
                <input type="tel" id="loginPhone" class="form-control-premium" placeholder="e.g. 98XXXXXXXX" required>
                <button type="submit" id="loginSubmit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i> Access My Portal
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 m-0" style="font-weight: 800; color: #1e293b;">Hi, <?php echo htmlspecialchars($_SESSION['customer_name']); ?> 👋</h2>
                <p class="text-muted m-0" style="font-size: 0.85rem;">Manage your salon experience</p>
            </div>
            <a href="#" class="btn btn-sm" style="min-height: auto; width: auto; color: #ef4444; background: #fff1f2; border-radius: 12px; font-weight: 600;" onclick="logoutCustomer()">Logout</a>
        </div>

        <div class="loyalty-premium">
            <div class="d-flex justify-content-between align-items-center">
                <span style="font-weight: 600; font-size: 0.9rem; opacity: 0.9;">Loyalty Rewards Program</span>
                <span style="font-weight: 800; font-size: 1.1rem;"><?php echo $customer_info['visit_count']; ?> Visits</span>
            </div>
            
            <?php 
            $visits = $customer_info['visit_count'];
            $cycle_size = 20;
            $effective_visits = (($visits - 1) % $cycle_size) + 1;
            if ($visits == 0) $effective_visits = 0;

            // Determine next sub-milestone (5, 10, 15, 20)
            $milestones = [5, 10, 15, 20];
            $next_milestone = 20;
            foreach ($milestones as $m) {
                if ($effective_visits < $m) {
                    $next_milestone = $m;
                    break;
                }
            }
            
            $progress = ($effective_visits / $next_milestone) * 100;
            ?>
            
            <div class="progress-track" style="margin-top: 30px;">
                <div class="progress-fill" style="width: <?php echo min(100, $progress); ?>%;"></div>
            </div>
            <div class="milestone-dots">
                <?php foreach ($milestones as $m): ?>
                    <div class="dot <?php echo ($effective_visits >= $m) ? 'active' : ''; ?>" title="<?php echo $m; ?> visits"></div>
                <?php endforeach; ?>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center" style="font-size: 0.8rem; opacity: 0.9;">
                <span><?php echo $next_milestone - $effective_visits; ?> more visits to unlock next gift!</span>
                <button class="btn btn-sm btn-link p-0 text-white" onclick="copyReferral('<?php echo $customer_info['referral_code']; ?>')" style="font-size: 0.75rem; text-decoration: none;">
                    Ref Code: <strong><?php echo $customer_info['referral_code']; ?></strong> <i class="far fa-copy ms-1"></i>
                </button>
            </div>
        </div>

        <h3 class="section-title"><i class="fas fa-gift text-primary"></i> My Rewards</h3>
        <div class="rewards-stack mb-4">
            <?php if (empty($rewards)): ?>
                <div class="premium-card text-center py-4">
                    <p class="text-muted m-0" style="font-size: 0.9rem;">You haven't earned any rewards yet.<br>Visit us to unlock exclusive perks!</p>
                </div>
            <?php else: ?>
                <?php foreach ($rewards as $reward): ?>
                    <?php 
                    $isLoyalty = $reward['reward_type'] === 'Loyalty';
                    $isAssigned = !empty($reward['assigned_service_id']);
                    $displayName = $reward['description'];
                    $needsAction = false;

                    if ($isLoyalty && !$isAssigned && $reward['status'] === 'Pending') {
                        $displayName = "💎 Specialized Reward Pending";
                        $needsAction = true;
                    }
                    ?>
                    <div class="premium-card d-flex justify-content-between align-items-center">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($displayName); ?></div>
                            <div class="info-row">
                                <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.65rem; border-radius: 6px;">
                                    <?php echo $reward['reward_type']; ?>
                                </span>
                                <span>• Earned <?php echo date('M j, Y', strtotime($reward['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php if ($reward['status'] === 'Pending'): ?>
                            <?php if ($needsAction): ?>
                                <span class="status-badge" style="background: #fdf2f8; color: #db2777;">Evaluating</span>
                            <?php else: ?>
                                <span class="status-badge" style="background: #f0fdf4; color: #16a34a;">Valid</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fas fa-check-circle text-muted" title="Claimed"></i>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h3 class="section-title"><i class="fas fa-calendar-check text-primary"></i> Active Tokens</h3>

        <?php if (empty($appointments)): ?>
            <div class="premium-card text-center py-5">
                <i class="fas fa-calendar-times mb-3" style="font-size: 3rem; color: #e2e8f0;"></i>
                <p class="text-muted">No active appointments found.</p>
                <a href="book_step1.php" class="btn btn-primary" style="min-height: auto; width: auto; display: inline-flex; padding: 10px 24px;">Book Now</a>
            </div>
        <?php else: ?>
            <div class="appointment-list">
                <?php foreach ($appointments as $token): ?>
                    <div class="premium-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="token-number"><?php echo $token['token_number']; ?></div>
                                <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">ID: #<?php echo $token['id']; ?></div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $token['status'])); ?>">
                                <?php echo $token['status']; ?>
                            </span>
                        </div>
                        
                        <div class="info-row">
                            <i class="far fa-calendar-alt"></i>
                            <strong>
                                <?php echo $token['appointment_date'] ? date('M j, Y', strtotime($token['appointment_date'])) : date('M j, Y', strtotime($token['created_at'])); ?>
                            </strong>
                            <?php if ($token['appointment_time']): ?>
                                <span class="ms-2"><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($token['appointment_time'])); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="info-row" style="margin-top: 10px;">
                            <i class="fas fa-magic"></i>
                            <span style="font-size: 0.85rem; color: #475569;"><?php echo htmlspecialchars($token['services_summary']); ?></span>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <a href="track_token.php?token_id=<?php echo $token['id']; ?>" class="btn btn-outline-secondary flex-grow-1" style="min-height: auto; border-radius: 12px; font-size: 0.85rem; padding: 12px;">
                                <i class="fas fa-radar me-1"></i> Track
                            </a>
                            <?php if ($token['status'] === 'Waiting'): ?>
                            <button class="btn btn-outline-danger flex-grow-1" style="min-height: auto; border-radius: 12px; font-size: 0.85rem; padding: 12px;" onclick="cancelToken(<?php echo $token['id']; ?>)">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="mt-5 text-center">
        <a href="../index.php" class="text-muted" style="font-size: 0.9rem; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="fas fa-home"></i> Back to Homepage
        </a>
    </div>
</div>

<script>
if (document.getElementById('loginForm')) {
    document.getElementById('loginForm').onsubmit = function(e) {
        e.preventDefault();
        const phone = document.getElementById('loginPhone').value.trim();
        const btn = document.getElementById('loginSubmit');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Authenticating...';
        
        const formData = new FormData();
        formData.append('phone', phone);
        
        fetch('../ajax/customer_login.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Access My Portal';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Connection error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Access My Portal';
        });
    };
}

function cancelToken(tokenId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) return;
    
    const formData = new FormData();
    formData.append('token_id', tokenId);
    
    fetch('../ajax/cancel_token.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Appointment cancelled successfully.');
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Connection error');
    });
}

function logoutCustomer() {
    fetch('../ajax/customer_logout.php', { method: 'POST', credentials: 'include' })
    .then(() => location.reload());
}

function copyReferral(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('Referral code copied to clipboard!');
    }).catch(err => {
        const el = document.createElement('textarea');
        el.value = code;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert('Referral code copied!');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
