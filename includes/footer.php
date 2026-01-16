        </main>
        <?php
        $renderGlobalFooter = $renderGlobalFooter ?? true;
        if ($renderGlobalFooter):
        ?>
        <?php if (($uiTheme ?? 'spare') === 'spare'): ?>
            <footer class="sp-footer">
                <div class="sp-container sp-footer-row">
                    <div>&copy; <?php echo date('Y'); ?> Motoshapi. All rights reserved.</div>
                    <div class="sp-footer-meta">
                        <button type="button" class="sp-footer-link" data-bs-toggle="modal" data-bs-target="#meetTeamModal">
                            Meet the Team
                        </button>
                    </div>
                </div>
            </footer>

            <div class="modal fade" id="meetTeamModal" tabindex="-1" aria-labelledby="meetTeamModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title w-100 text-center" id="meetTeamModalLabel">Meet the Team</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row justify-content-center g-4">
                                <?php
                                $teamMembers = [];
                                try {
                                    if (isset($conn) && $conn instanceof PDO) {
                                        $about_stmt = $conn->query("SELECT * FROM about_us ORDER BY id ASC");
                                        $teamMembers = $about_stmt ? $about_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                                    }
                                } catch (Throwable $e) {
                                    $teamMembers = [];
                                }
                                ?>

                                <?php if (empty($teamMembers)): ?>
                                    <div class="col-12" style="text-align: center; color: #6c757d; padding: 18px;">
                                        No team members found.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($teamMembers as $member): ?>
                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 d-flex">
                                            <div class="card sp-team-card w-100 h-100 text-center shadow-sm">
                                                <?php if(!empty($member['photo_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($member['photo_url']); ?>" class="sp-team-avatar mx-auto d-block rounded-circle shadow-sm" alt="<?php echo htmlspecialchars($member['name'] ?? 'Team member'); ?>">
                                                <?php else: ?>
                                                    <div class="sp-team-avatar sp-team-avatar--empty bg-secondary text-white d-flex align-items-center justify-content-center mx-auto rounded-circle">No Photo</div>
                                                <?php endif; ?>
                                                <div class="card-body px-3">
                                                    <h4 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($member['name'] ?? ''); ?></h4>
                                                    <?php if (!empty($member['description'])): ?>
                                                        <div class="sp-team-role"><?php echo htmlspecialchars($member['description']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <footer class="modern-footer">
                <div class="modern-container">
                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-3">
                        <div style="color: var(--text-secondary); font-size: 0.875rem;">&copy; <?php echo date('Y'); ?> Motoshapi. All rights reserved.</div>
                    </div>
                </div>
            </footer>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div id="ms-chatbot" class="ms-chatbot" aria-live="polite">
        <button id="ms-chatbot-toggle" class="ms-chatbot-toggle" type="button" aria-expanded="false" aria-controls="ms-chatbot-panel" title="Chat">
            <i class="bi bi-chat-dots" style="font-size: 1.25rem;"></i>
        </button>

        <div id="ms-chatbot-panel" class="ms-chatbot-panel" hidden>
            <div class="ms-chatbot-header">
                <div class="ms-chatbot-title">Motoshapi Assistant</div>
                <div class="ms-chatbot-header-actions">
                    <button id="ms-chatbot-close" class="ms-chatbot-close" type="button" title="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div id="ms-chatbot-messages" class="ms-chatbot-messages"></div>
            <form id="ms-chatbot-form" class="ms-chatbot-form" autocomplete="off">
                <input id="ms-chatbot-input" class="ms-chatbot-input" type="text" placeholder="Type a message..." />
                <button id="ms-chatbot-send" class="ms-chatbot-send" type="submit">Send</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php $assetBase = $assetBase ?? 'assets'; ?>
    <script src="<?php echo $assetBase; ?>/js/chatbot-widget.js"></script>
</body>
</html>