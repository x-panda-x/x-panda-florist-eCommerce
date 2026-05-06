<?php
$customer = is_array($customer ?? null) ? $customer : [];
$reminders = is_array($reminders ?? null) ? $reminders : [];
$createFormData = is_array($createFormData ?? null) ? $createFormData : [];
$draftReminder = is_array($draftReminder ?? null) ? $draftReminder : [];
?>
<?php require __DIR__ . '/../components/account-nav.php'; ?>

<div class="account-content">
    <?php if (!empty($success)): ?>
        <div class="flash flash-success mb-4"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="flash flash-error mb-4"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="stack-xl">
        <div class="account-card">
            <div class="account-card-header">
                <h2>Saved Reminders</h2>
                <p class="site-note">Track upcoming floral occasions.</p>
            </div>

            <?php if ($reminders === []): ?>
                <div class="empty-state">
                    <p class="eyebrow">No Saved Reminders</p>
                    <h3 class="serif-head" style="margin-bottom:1rem;font-size:1.5rem;">You haven't added any reminders yet.</h3>
                    <p class="site-note">Add a reminder below to never miss a special occasion.</p>
                </div>
            <?php else: ?>
                <div class="stack-md">
                    <?php foreach ($reminders as $reminder): ?>
                        <div class="detail-card" style="display:flex;gap:2rem;flex-wrap:wrap;align-items:flex-start;">
                            <div class="stack-sm" style="flex:1 1 300px;">
                                <div style="display:flex;justify-content:flex-start;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                                    <strong style="color:var(--color-black);font-size:1.1rem;font-family:var(--font-heading);text-transform:uppercase;"><?php echo htmlspecialchars((string) ($reminder['occasion_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="status-pill status-pill--<?php echo htmlspecialchars((string) ($reminder['status_tone'] ?? 'info'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($reminder['status_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <span class="site-note"><strong>Mode:</strong> <?php echo htmlspecialchars((string) ($reminder['mode_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="site-note"><strong>Recipient:</strong> <?php echo htmlspecialchars((string) ($reminder['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="site-note"><strong>Date:</strong> <?php echo htmlspecialchars((string) ($reminder['reminder_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($reminder['product_name'])): ?>
                                    <span class="site-note"><strong>Product:</strong> <?php echo htmlspecialchars((string) ($reminder['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($reminder['order_number'])): ?>
                                    <span class="site-note"><strong>Paid Order:</strong> <?php echo htmlspecialchars((string) ($reminder['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($reminder['note'])): ?>
                                    <span class="site-note" style="white-space:pre-line;"><strong>Note:</strong> <?php echo htmlspecialchars((string) ($reminder['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($reminder['last_sent_at'])): ?>
                                    <span class="site-note"><strong>Last Sent:</strong> <?php echo htmlspecialchars((string) ($reminder['last_sent_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($reminder['action_required_by'])): ?>
                                    <span class="site-note"><strong>Action By:</strong> <?php echo htmlspecialchars((string) ($reminder['action_required_by'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if (($reminder['status'] ?? '') === 'action_needed'): ?>
                                    <span class="site-note">No purchase is linked yet. Complete an order within <?php echo htmlspecialchars((string) ($reminder['action_window_hours'] ?? 48), ENT_QUOTES, 'UTF-8'); ?> hours of the reminder email to keep this actionable.</span>
                                <?php elseif (($reminder['status'] ?? '') === 'expiring_soon'): ?>
                                    <span class="site-note">This reminder is close to expiring because no purchase has been linked yet.</span>
                                <?php elseif (($reminder['status'] ?? '') === 'expired'): ?>
                                    <span class="site-note">This reminder expired after the action window passed without a linked purchase.</span>
                                <?php elseif (($reminder['status'] ?? '') === 'purchased'): ?>
                                    <span class="site-note">A paid order is already attached to this reminder.</span>
                                <?php endif; ?>

                                <form method="post" action="/account/reminders/toggle" style="margin-top:0.75rem;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="reminder_id" value="<?php echo htmlspecialchars((string) ($reminder['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="site-note" style="background:none;border:none;padding:0;text-decoration:underline;cursor:pointer;color:var(--color-gray-dark);font-family:var(--font-heading);text-transform:uppercase;font-size:0.7rem;"><?php echo !empty($reminder['is_active']) ? 'Pause Reminder' : 'Reactivate Reminder'; ?></button>
                                </form>
                            </div>

                            <div style="flex:2 1 400px;">
                                <form method="post" action="/account/reminders/update" class="stack-md">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="reminder_id" value="<?php echo htmlspecialchars((string) ($reminder['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="field-grid cols-2">
                                        <div class="field-group">
                                            <label for="occasion_label-<?php echo (int) ($reminder['id'] ?? 0); ?>">Occasion</label>
                                            <input id="occasion_label-<?php echo (int) ($reminder['id'] ?? 0); ?>" name="occasion_label" type="text" required value="<?php echo htmlspecialchars((string) ($reminder['occasion_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.6rem;font-size:0.9rem;">
                                        </div>
                                        <div class="field-group">
                                            <label for="recipient_name-<?php echo (int) ($reminder['id'] ?? 0); ?>">Recipient Name</label>
                                            <input id="recipient_name-<?php echo (int) ($reminder['id'] ?? 0); ?>" name="recipient_name" type="text" required value="<?php echo htmlspecialchars((string) ($reminder['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.6rem;font-size:0.9rem;">
                                        </div>
                                    </div>
                                    <div class="field-grid cols-2">
                                        <div class="field-group">
                                            <label for="reminder_date-<?php echo (int) ($reminder['id'] ?? 0); ?>">Date</label>
                                            <input id="reminder_date-<?php echo (int) ($reminder['id'] ?? 0); ?>" name="reminder_date" type="date" required value="<?php echo htmlspecialchars((string) ($reminder['reminder_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.6rem;font-size:0.9rem;">
                                        </div>
                                        <div class="field-group">
                                            <label for="note-<?php echo (int) ($reminder['id'] ?? 0); ?>">Note</label>
                                            <textarea id="note-<?php echo (int) ($reminder['id'] ?? 0); ?>" name="note" rows="1" style="padding:0.6rem;font-size:0.9rem;"><?php echo htmlspecialchars((string) ($reminder['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    </div>
                                    <label class="site-note" style="display:flex;gap:1rem;align-items:flex-start;margin-top:1rem;">
                                        <input type="checkbox" name="is_active" value="1" <?php echo !empty($reminder['is_active']) ? 'checked' : ''; ?> style="width:1.1rem;height:1.1rem;margin-top:0.2rem;padding:0;">
                                        <span>Keep this reminder active.</span>
                                    </label>
                                    <div style="margin-top:1rem;display:flex;justify-content:flex-end;gap:1.5rem;">
                                        <button type="submit" formaction="/account/reminders/delete" class="btn-secondary" style="padding:0.6rem 1.25rem;font-size:0.75rem;border-color:var(--color-rose-line);color:var(--color-gray-dark);">Delete</button>
                                        <button type="submit" class="btn" style="padding:0.6rem 2rem;font-size:0.75rem;">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="account-card" style="margin-top:4rem;">
            <div class="account-card-header">
                <h2>Add New Reminder</h2>
                <p class="site-note">Save reminders with or without an immediate purchase. You can keep a reminder active now, or save it and continue shopping to attach a paid order later.</p>
            </div>

            <?php if ($draftReminder !== []): ?>
                <div class="detail-card" style="margin-bottom:1.5rem;">
                    <p class="eyebrow" style="margin-bottom:0.75rem;">Reminder Draft Active</p>
                    <p class="site-note">Your reminder is already saved. Continue shopping and complete payment if you want this reminder linked to a real order.</p>
                    <?php if (!empty($draftReminder['occasion_label'])): ?>
                        <p class="site-note"><strong>Occasion:</strong> <?php echo htmlspecialchars((string) ($draftReminder['occasion_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($draftReminder['product_slug'])): ?>
                        <p class="site-note"><strong>Current Product Selection:</strong> Linked automatically from your storefront shopping flow.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/account/reminders/create" class="stack-lg">
                <?php echo csrf_field(); ?>
                <div class="field-grid cols-2">
                    <div class="field-group">
                        <label for="occasion_label">Occasion (e.g. Birthday, Anniversary)</label>
                        <input id="occasion_label" name="occasion_label" type="text" required value="<?php echo htmlspecialchars((string) ($createFormData['occasion_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Birthday, Anniversary, Mother's Day">
                    </div>
                    <div class="field-group">
                        <label for="recipient_name">Recipient Name</label>
                        <input id="recipient_name" name="recipient_name" type="text" required value="<?php echo htmlspecialchars((string) ($createFormData['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="field-grid cols-2">
                    <div class="field-group">
                        <label for="reminder_date">Reminder Date</label>
                        <input id="reminder_date" name="reminder_date" type="date" required value="<?php echo htmlspecialchars((string) ($createFormData['reminder_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field-group">
                        <label for="note">Note</label>
                        <textarea id="note" name="note" rows="2"><?php echo htmlspecialchars((string) ($createFormData['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
                <label class="site-note" style="display:flex;gap:1.5rem;align-items:flex-start;padding:1.5rem;background:rgba(255,255,255,0.8);border:1px solid var(--color-gray-light);border-radius:6px;cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" <?php echo !array_key_exists('is_active', $createFormData) || !empty($createFormData['is_active']) ? 'checked' : ''; ?> style="width:1.25rem;height:1.25rem;margin-top:0.1rem;padding:0;">
                    <span style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.85rem;">Keep this reminder active.</span>
                </label>
                <div class="account-reminder-create-actions" style="margin-top:1rem;display:flex;gap:1rem;flex-wrap:wrap;">
                    <button type="submit" name="create_action" value="save" class="btn-secondary" style="min-width: 220px;">Save Reminder Only</button>
                    <button type="submit" name="create_action" value="shop" class="btn" style="min-width: 260px;">Save Reminder And Start Shopping</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div> <!-- .account-wrap -->
</div> <!-- .container -->
