<?php
$content = file_get_contents("admin.php");

// Extract Post Modals
$postModalRegex = '/<!-- Modal for deleting post -->.*?<\/div>.*?<\/div>.*?<\/div>.*?<\/div>/s';
preg_match($postModalRegex, $content, $postModalMatch);
$postModalHTML = $postModalMatch[0];
$content = preg_replace($postModalRegex, "<!-- Admin delete post modals moved to bottom -->", $content);

// Extract User Modals
$userModalRegex = '/<!-- Change Role Modal -->.*?<!-- Delete User Modal -->.*?<\/form>.*?<\/div>.*?<\/div>.*?<\/div>.*?<\/div>.*?<\?php endif; \?>/s';
preg_match($userModalRegex, $content, $userModalMatch);
$userModalHTML = $userModalMatch[0];
// Need to modify $userModalHTML slightly to wrap it in the loops correctly
$userModalHTML = str_replace("<?php endif; ?>", "    <?php endif; ?>", $userModalHTML);

$content = preg_replace($userModalRegex, "<!-- User modals moved to bottom -->\n                                        <?php endif; ?>", $content);

$bottomHTML = "
<!--
     MODALS (Rendered outside table-responsive)
   -->
<!-- Post Modals -->
<?php foreach (\$allPosts as \$p): ?>
" . $postModalHTML . "
<?php endforeach; ?>

<!-- User Modals -->
<?php foreach (\$allUsers as \$u): ?>
    <?php \$isSelf = ((int)\$u['id'] === \$currentUserId); ?>
    <?php if (!\$isSelf): ?>
" . $userModalHTML . "
<?php endforeach; ?>

<script>";

$content = str_replace("<script>", $bottomHTML, $content);

file_put_contents("admin.php", $content);
echo "Done refactoring.";

