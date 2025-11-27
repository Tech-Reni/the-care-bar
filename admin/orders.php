<?php
$page_title = 'Orders';
require_once __DIR__ . '/header.php';

$orders = getAllOrders();

// Handle marking completed via POST (also available via API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'complete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $result = updateOrderStatus($id, 'Completed');
        if ($result) {
            $_SESSION['order_message'] = ['type' => 'success', 'text' => 'Order marked as completed successfully!'];
        } else {
            $_SESSION['order_message'] = ['type' => 'error', 'text' => 'Failed to update order status.'];
        }
        header('Location: ' . $BASE_URL . 'admin/orders.php');
        exit;
    }
}

// Get flash message if any
$flashMessage = null;
if (isset($_SESSION['order_message'])) {
    $flashMessage = $_SESSION['order_message'];
    unset($_SESSION['order_message']);
}
?>

<div class="container">
    <h1>Orders</h1>

    <?php if ($flashMessage): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const msg = <?php echo json_encode($flashMessage); ?>;
                if (msg.type === 'success') {
                    showSuccess('Success', msg.text);
                } else if (msg.type === 'error') {
                    showError('Error', msg.text);
                }
            });
        </script>
    <?php endif; ?>

    <div class="table-container" style="margin-top:20px;">
        <?php if (!empty($orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Placed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?php echo $o['id']; ?></td>
                            <td><?php echo htmlspecialchars($o['order_number']); ?></td>
                            <td><?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?><br><?php echo htmlspecialchars($o['email']); ?></td>
                            <td style="max-width:300px;">
                                <?php
                                    $items = getOrderItems($o['id']);
                                    foreach ($items as $it) {
                                        echo '<div style="font-size:13px;">' . htmlspecialchars($it['name'] ?? 'Item') . ' x' . intval($it['quantity'] ?? 1) . '</div>';
                                    }
                                ?>
                            </td>
                            <td>₦<?php echo number_format($o['total'], 2); ?></td>
                            <td><?php echo htmlspecialchars($o['status']); ?></td>
                            <td><?php echo htmlspecialchars($o['created_at']); ?></td>
                            <td>
                                <a class="btn btn-sm" href="order_view.php?id=<?php echo $o['id']; ?>">View</a>
                                <?php if ($o['status'] !== 'Completed'): ?>
                                    <button class="btn btn-sm btn-primary" type="button" onclick="confirmCompleteOrder(<?php echo (int)$o['id']; ?>)">Mark Completed</button>
                                <?php else: ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    /**
     * Show confirmation modal and submit form on confirm
     */
    function confirmCompleteOrder(orderId) {
        showConfirm(
            'Mark Order Completed?',
            'Are you sure you want to mark this order as completed? This action cannot be undone.',
            function() {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="id" value="${orderId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        );
    }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
<?php require_once __DIR__ . '/../includes/modal.php'; ?>
