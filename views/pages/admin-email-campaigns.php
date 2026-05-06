<div class="admin-form-shell">
    <p class="admin-kicker">Marketing</p>
    <h2 class="admin-title">Email Campaigns</h2>
    <p class="admin-subtitle">Send one branded announcement to selected opted-in customers.</p>
    <?php if (!empty($error)): ?><div class="admin-alert error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="admin-alert success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <form method="get" action="/admin/email-campaigns" class="admin-grid cols-3" style="align-items:end;">
        <div class="admin-field"><label for="search">Search Customers</label><input id="search" name="search" type="search" value="<?php echo htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
        <div class="admin-field"><label for="filter">Audience</label><select id="filter" name="filter"><option value="subscribed" <?php echo ($filter ?? '') === 'subscribed' ? 'selected' : ''; ?>>Subscribed Only</option><option value="all" <?php echo ($filter ?? '') === 'all' ? 'selected' : ''; ?>>All Active</option><option value="orders" <?php echo ($filter ?? '') === 'orders' ? 'selected' : ''; ?>>With Orders</option><option value="reminders" <?php echo ($filter ?? '') === 'reminders' ? 'selected' : ''; ?>>With Reminders</option></select></div>
        <div><button type="submit" class="admin-button-secondary">Apply</button></div>
    </form>

    <form method="post" action="/admin/email-campaigns/send" class="admin-grid cols-2" style="margin-top:1rem;">
        <?php echo csrf_field(); ?>
        <div class="admin-card admin-form-section" style="grid-column:1 / -1;padding:1.1rem;">
            <p class="admin-kicker">Recipients</p>
            <div class="admin-multi-controls" data-admin-multi-controls data-admin-filter-target="campaign_customer_choices">
                <div class="admin-multi-controls__actions">
                    <button type="button" class="admin-button-secondary" data-admin-multi-action="all">Select All</button>
                    <button type="button" class="admin-button-secondary" data-admin-multi-action="visible">Select Visible</button>
                    <button type="button" class="admin-button-secondary" data-admin-multi-action="clear">Clear All</button>
                </div>
                <p class="admin-note admin-multi-controls__count" data-admin-multi-count>0 selected</p>
            </div>
            <div class="admin-campaign-selected" data-campaign-selected-panel>
                <p class="admin-campaign-selected__title">Selected Recipients</p>
                <p class="admin-note admin-campaign-selected__empty" data-campaign-selected-empty>No recipients selected yet.</p>
                <div class="admin-campaign-selected__list" data-campaign-selected-list></div>
            </div>
            <div id="campaign_customer_choices" data-admin-filter-list class="admin-grid cols-2">
                <?php foreach (($recipients ?? []) as $customer): ?>
                    <label class="admin-checkbox admin-soft-card admin-card" data-admin-filter-item data-admin-filter-text="<?php echo htmlspecialchars((string) (($customer['full_name'] ?? '') . ' ' . ($customer['email'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.85rem;">
                        <input type="checkbox" name="customer_ids[]" value="<?php echo (int) ($customer['id'] ?? 0); ?>" data-campaign-recipient-name="<?php echo htmlspecialchars((string) ($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-campaign-recipient-email="<?php echo htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <span><strong><?php echo htmlspecialchars((string) ($customer['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong><br><small><?php echo htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> • Orders: <?php echo (int) ($customer['order_count'] ?? 0); ?> • Reminders: <?php echo (int) ($customer['reminder_count'] ?? 0); ?><?php echo !empty($customer['marketing_opt_in']) ? ' • Subscribed' : ' • Unsubscribed'; ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-field" style="grid-column:1 / -1;"><label for="subject">Subject</label><input id="subject" name="subject" type="text" required></div>
        <div class="admin-field" style="grid-column:1 / -1;"><label for="preheader">Preheader / Preview Text</label><input id="preheader" name="preheader" type="text"></div>
        <div class="admin-field" style="grid-column:1 / -1;"><label for="message_body">Message Body</label><textarea id="message_body" name="message_body" rows="8" required></textarea></div>
        <div class="admin-field"><label for="cta_label">CTA Label (Optional)</label><input id="cta_label" name="cta_label" type="text"></div>
        <div class="admin-field"><label for="cta_url">CTA URL (Optional)</label><input id="cta_url" name="cta_url" type="url"></div>
        <div class="admin-field"><label for="qa_override_email">QA Override Email (optional)</label><input id="qa_override_email" name="qa_override_email" type="email" placeholder="pouyasetode111@gmail.com"></div>
        <div class="admin-field"><label class="admin-checkbox"><input type="checkbox" name="is_test_only" value="1"><span>Send test only (first target)</span></label></div>
        <div class="admin-form-actions" style="grid-column:1 / -1;display:flex;gap:0.8rem;"><button type="submit" class="admin-button" onclick="return confirm('You are about to send this campaign to selected recipients. Continue?');">Send Campaign</button></div>
    </form>

    <div class="admin-card" style="padding:1.1rem;">
        <p class="admin-kicker">Recent Campaign Log</p>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Time</th><th>Subject</th><th>Selected</th><th>Targets</th><th>Sent</th><th>Failed</th></tr></thead>
                <tbody>
                    <?php foreach (($campaignLogs ?? []) as $row): ?>
                        <tr><td><?php echo htmlspecialchars((string) ($row['timestamp'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($row['subject'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) ($row['selected_count'] ?? 0); ?></td><td><?php echo (int) ($row['actual_target_count'] ?? 0); ?></td><td><?php echo (int) ($row['sent'] ?? 0); ?></td><td><?php echo (int) ($row['failed'] ?? 0); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    (function () {
        var panel = document.querySelector('[data-campaign-selected-panel]');
        var list = document.querySelector('[data-campaign-selected-list]');
        var empty = document.querySelector('[data-campaign-selected-empty]');
        var choices = document.getElementById('campaign_customer_choices');
        if (!panel || !list || !empty || !choices) {
            return;
        }

        function allBoxes() {
            return Array.prototype.slice.call(choices.querySelectorAll('input[name="customer_ids[]"]'));
        }

        function renderSelected() {
            list.innerHTML = '';
            var selected = allBoxes().filter(function (box) { return box.checked; });
            empty.hidden = selected.length > 0;
            selected.forEach(function (box) {
                var id = box.value;
                var name = box.getAttribute('data-campaign-recipient-name') || 'Customer';
                var email = box.getAttribute('data-campaign-recipient-email') || '';
                var item = document.createElement('div');
                item.className = 'admin-campaign-selected__item';
                item.innerHTML = '<div class=\"admin-campaign-selected__meta\"><strong>' + name + '</strong><small>' + email + '</small></div>'
                    + '<button type=\"button\" class=\"admin-button-secondary\" data-campaign-remove-id=\"' + id + '\">Remove from this campaign</button>';
                list.appendChild(item);
            });
        }

        choices.addEventListener('change', function (event) {
            if (event.target && event.target.name === 'customer_ids[]') {
                renderSelected();
            }
        });

        panel.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-campaign-remove-id]');
            if (!btn) {
                return;
            }
            var id = btn.getAttribute('data-campaign-remove-id') || '';
            var box = choices.querySelector('input[name=\"customer_ids[]\"][value=\"' + id + '\"]');
            if (box) {
                box.checked = false;
                renderSelected();
            }
        });

        document.querySelectorAll('[data-admin-multi-controls] [data-admin-multi-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.setTimeout(renderSelected, 0);
            });
        });

        renderSelected();
    }());
</script>
