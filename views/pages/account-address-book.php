<?php
$customer = is_array($customer ?? null) ? $customer : [];
$addresses = is_array($addresses ?? null) ? $addresses : [];
$createFormData = is_array($createFormData ?? null) ? $createFormData : [];
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
                <h2>Saved Addresses</h2>
                <p class="site-note">Manage saved delivery addresses. One default address per customer.</p>
            </div>

            <?php if ($addresses === []): ?>
                <div class="empty-state">
                    <p class="eyebrow">No Saved Addresses</p>
                    <h3 class="serif-head" style="margin-bottom:1rem;font-size:1.5rem;">You haven't added any addresses yet.</h3>
                    <p class="site-note">Add an address below to start building your address book.</p>
                </div>
            <?php else: ?>
                <div class="stack-md">
                    <?php foreach ($addresses as $address): ?>
                        <div class="detail-card" style="display:flex;gap:2rem;flex-wrap:wrap;align-items:flex-start;">
                            <div class="stack-sm" style="flex:1 1 300px;">
                                <div style="display:flex;justify-content:flex-start;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                                    <strong style="color:var(--color-black);font-size:1.1rem;font-family:var(--font-heading);text-transform:uppercase;"><?php echo htmlspecialchars((string) ($address['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if (!empty($address['is_default'])): ?>
                                        <span class="status-pill">Default</span>
                                    <?php endif; ?>
                                </div>
                                <span class="site-note"><strong>Recipient:</strong> <?php echo htmlspecialchars((string) ($address['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="site-note" style="white-space:pre-line;"><?php echo htmlspecialchars((string) ($address['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="site-note"><strong>ZIP:</strong> <?php echo htmlspecialchars((string) ($address['delivery_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($address['delivery_instructions'])): ?>
                                    <span class="site-note"><strong>Instructions:</strong> <?php echo htmlspecialchars((string) ($address['delivery_instructions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                
                                <?php if (empty($address['is_default'])): ?>
                                    <form method="post" action="/account/addresses/default" style="margin-top:0.75rem;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="address_id" value="<?php echo htmlspecialchars((string) ($address['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="site-note" style="background:none;border:none;padding:0;text-decoration:underline;cursor:pointer;color:var(--color-gray-dark);font-family:var(--font-heading);text-transform:uppercase;font-size:0.7rem;">Make Default</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div style="flex:2 1 400px;">
                                <form method="post" action="/account/addresses/update" class="stack-md">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="address_id" value="<?php echo htmlspecialchars((string) ($address['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="field-grid cols-2">
                                        <div class="field-group">
                                            <label for="label-<?php echo (int) ($address['id'] ?? 0); ?>">Label</label>
                                            <input id="label-<?php echo (int) ($address['id'] ?? 0); ?>" name="label" type="text" required value="<?php echo htmlspecialchars((string) ($address['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.6rem;font-size:0.9rem;">
                                        </div>
                                        <div class="field-group">
                                            <label for="recipient_name-<?php echo (int) ($address['id'] ?? 0); ?>">Recipient Name</label>
                                            <input id="recipient_name-<?php echo (int) ($address['id'] ?? 0); ?>" name="recipient_name" type="text" required value="<?php echo htmlspecialchars((string) ($address['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.6rem;font-size:0.9rem;">
                                        </div>
                                    </div>
                                    <div class="field-group">
                                        <label for="delivery_address-<?php echo (int) ($address['id'] ?? 0); ?>">Delivery Address</label>
                                        <textarea id="delivery_address-<?php echo (int) ($address['id'] ?? 0); ?>" name="delivery_address" rows="2" required style="padding:0.6rem;font-size:0.9rem;"><?php echo htmlspecialchars((string) ($address['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                    <div class="field-grid cols-2">
                                        <div class="field-group">
                                            <label for="delivery_zip-<?php echo (int) ($address['id'] ?? 0); ?>">ZIP Code</label>
                                            <input id="delivery_zip-<?php echo (int) ($address['id'] ?? 0); ?>" name="delivery_zip" type="text" inputmode="numeric" maxlength="5" required value="<?php echo htmlspecialchars((string) ($address['delivery_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="padding:0.6rem;font-size:0.9rem;">
                                        </div>
                                        <div class="field-group">
                                            <label for="delivery_instructions-<?php echo (int) ($address['id'] ?? 0); ?>">Instructions</label>
                                            <textarea id="delivery_instructions-<?php echo (int) ($address['id'] ?? 0); ?>" name="delivery_instructions" rows="1" style="padding:0.6rem;font-size:0.9rem;"><?php echo htmlspecialchars((string) ($address['delivery_instructions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                        </div>
                                    </div>
                                    <div style="margin-top:1rem;display:flex;justify-content:flex-end;gap:1.5rem;">
                                        <button type="submit" formaction="/account/addresses/delete" class="btn-secondary" style="padding:0.6rem 1.25rem;font-size:0.75rem;border-color:var(--color-rose-line);color:var(--color-gray-dark);">Delete</button>
                                        <button type="submit" class="btn" style="padding:0.6rem 2rem;font-size:0.75rem;">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="account-card" style="margin-top: 4rem;">
            <div class="account-card-header">
                <h2>Add New Address</h2>
            </div>
            <form method="post" action="/account/addresses/create" class="stack-lg">
                <?php echo csrf_field(); ?>
                <div class="field-grid cols-2">
                    <div class="field-group">
                        <label for="label">Label (e.g. Home, Mom's)</label>
                        <input id="label" name="label" type="text" required value="<?php echo htmlspecialchars((string) ($createFormData['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Home, Office, Mom">
                    </div>
                    <div class="field-group">
                        <label for="recipient_name">Recipient Name</label>
                        <input id="recipient_name" name="recipient_name" type="text" required value="<?php echo htmlspecialchars((string) ($createFormData['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>
                <div class="field-group">
                    <label for="delivery_address">Delivery Address</label>
                    <textarea id="delivery_address" name="delivery_address" rows="3" required><?php echo htmlspecialchars((string) ($createFormData['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="field-grid cols-2">
                    <div class="field-group">
                        <label for="delivery_zip">Delivery ZIP Code</label>
                        <input id="delivery_zip" name="delivery_zip" type="text" inputmode="numeric" maxlength="5" required value="<?php echo htmlspecialchars((string) ($createFormData['delivery_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="field-group">
                        <label for="delivery_instructions">Delivery Instructions</label>
                        <textarea id="delivery_instructions" name="delivery_instructions" rows="2"><?php echo htmlspecialchars((string) ($createFormData['delivery_instructions'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
                <label class="site-note" style="display:flex;gap:1.5rem;align-items:flex-start;padding:1.5rem;background:rgba(255,255,255,0.8);border:1px solid var(--color-gray-light);border-radius:6px;cursor:pointer;">
                    <input type="checkbox" name="is_default" value="1" <?php echo !empty($createFormData['is_default']) ? 'checked' : ''; ?> style="width:1.25rem;height:1.25rem;margin-top:0.1rem;padding:0;">
                    <span style="color:var(--color-black);font-family:var(--font-heading);text-transform:uppercase;letter-spacing:0.1em;font-size:0.85rem;">Make this my default delivery address.</span>
                </label>
                <div style="margin-top:1rem;">
                    <button type="submit" class="btn" style="min-width: 240px;">Save New Address</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div> <!-- .account-wrap -->
</div> <!-- .container -->
