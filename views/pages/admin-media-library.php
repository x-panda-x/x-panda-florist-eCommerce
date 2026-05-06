<?php
$assets = is_array($assets ?? null) ? $assets : [];
$collectionKey = (string) ($collectionKey ?? '');
$collectionOptions = is_array($collectionOptions ?? null) ? $collectionOptions : [];
require_once BASE_PATH . '/views/components/admin-selection-controls.php';
?>
<div class="admin-form-shell">
    <p class="admin-kicker">Website</p>
    <h2 class="admin-title">Media Library</h2>
    <p class="admin-subtitle">Choose a collection first, then upload or manage website images in that collection.</p>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success" style="margin-top:1rem;"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="admin-card admin-form-section admin-form-section--website">
        <h3 class="admin-section-title">Filter Assets</h3>
        <p class="admin-section-subtitle">Choose an existing collection. Use Custom only if you need a new collection key.</p>
        <form method="get" action="/admin/media" class="admin-grid cols-2" style="margin-top:1rem;">
            <?php
            admin_selection_render_hybrid_field([
                'id' => 'media_collection_key_filter',
                'name' => 'collection_key',
                'label' => 'Collection',
                'value' => $collectionKey,
                'options' => $collectionOptions,
                'empty_label' => 'All collections',
                'custom_label' => 'Custom collection key',
                'custom_input_label' => 'Custom Collection Key',
                'placeholder' => 'homepage-hero',
                'note' => 'Current filter: ' . ($collectionKey !== '' ? $collectionKey : 'All collections'),
            ]);
            ?>
            <div style="display:flex;gap:0.8rem;align-items:flex-end;">
                <button type="submit" class="admin-button-secondary">Apply Filter</button>
                <a href="/admin/media" class="admin-button-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="admin-card admin-form-section admin-form-section--content">
        <h3 class="admin-section-title">Upload New Asset</h3>
        <p class="admin-section-subtitle">Pick a collection so images are easy to find later.</p>
        <form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="admin-grid cols-2" style="margin-top:1rem;">
            <?php echo csrf_field(); ?>

            <?php
            admin_selection_render_hybrid_field([
                'id' => 'media_collection_key',
                'name' => 'collection_key',
                'label' => 'Choose Collection',
                'value' => $collectionKey,
                'options' => $collectionOptions,
                'empty_label' => 'Choose a collection',
                'custom_label' => 'Custom collection key',
                'custom_input_label' => 'Custom Collection Key',
                'placeholder' => 'homepage-hero',
                'required' => true,
            ]);
            ?>

            <div class="admin-field">
                <label for="asset">Image File</label>
                <input id="asset" name="asset" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml" required>
            </div>

            <div class="admin-field" style="grid-column:1 / -1;">
                <label for="upload_alt_text">Alt Text</label>
                <input id="upload_alt_text" name="alt_text" type="text" value="" placeholder="Describe what appears in this image">
            </div>

            <div style="grid-column:1 / -1;">
                <button type="submit" class="admin-button">Upload Asset</button>
            </div>
        </form>
    </div>

    <div class="admin-card admin-form-section admin-form-section--status">
        <h3 class="admin-section-title">Assets In This View</h3>
        <p class="admin-section-subtitle">Showing <?php echo htmlspecialchars((string) count($assets), ENT_QUOTES, 'UTF-8'); ?> asset(s) for <?php echo htmlspecialchars($collectionKey !== '' ? $collectionKey : 'all collections', ENT_QUOTES, 'UTF-8'); ?>.</p>
        <div class="admin-table-wrap" style="margin-top:1rem;margin-bottom:0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Asset</th>
                        <th>Collection</th>
                        <th>Metadata</th>
                        <th>Alt Text</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assets === []): ?>
                        <tr>
                            <td colspan="5">
                                <div class="admin-note">No media assets matched this filter.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td style="width:8rem;">
                                    <img
                                        src="<?php echo htmlspecialchars((string) ($asset['public_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?php echo htmlspecialchars((string) ($asset['alt_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        style="width:6rem;height:6rem;object-fit:cover;border-radius:12px;border:1px solid rgba(215, 199, 186, 0.74);background:#fff;"
                                    >
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars((string) ($asset['original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="admin-note"><?php echo htmlspecialchars((string) ($asset['public_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="admin-note"><?php echo htmlspecialchars((string) ($asset['mime_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars((string) ($asset['collection_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="admin-note">ID: <?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="admin-note">Size: <?php echo htmlspecialchars((string) number_format(((int) ($asset['file_size'] ?? 0)) / 1024, 1), ENT_QUOTES, 'UTF-8'); ?> KB</div>
                                    <div class="admin-note">Dimensions: <?php echo htmlspecialchars((string) (($asset['width'] ?? '?') . ' x ' . ($asset['height'] ?? '?')), ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td style="min-width:20rem;">
                                    <form method="post" action="/admin/media/update-alt" class="stack-md">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="asset_id" value="<?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="collection_key" value="<?php echo htmlspecialchars((string) ($collectionKey !== '' ? $collectionKey : ($asset['collection_key'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="admin-field" style="margin-bottom:0;">
                                            <label for="alt_text_<?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Alt Text</label>
                                            <input id="alt_text_<?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" name="alt_text" type="text" value="<?php echo htmlspecialchars((string) ($asset['alt_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <button type="submit" class="admin-button-secondary">Save Alt Text</button>
                                    </form>
                                    <form method="post" action="/admin/media/delete" onsubmit="return confirm('Delete this media asset? Assets assigned to website content are protected.');" style="margin-top:0.8rem;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="asset_id" value="<?php echo htmlspecialchars((string) ($asset['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="collection_key" value="<?php echo htmlspecialchars((string) ($collectionKey !== '' ? $collectionKey : ($asset['collection_key'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="admin-text-button" style="border:0;background:none;color:#8b3c39;padding:0;">Delete Asset</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
