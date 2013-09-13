<?php
/**
 * @file
 * Theme a radio button.
 */
?>
<input type="radio" <?php print \Perseus\System::htmlAttributes($attributes['input']); ?> />&nbsp;<label <?php print \Perseus\System::htmlAttributes($attributes['label']); ?>><?php print filter_xss($label); ?></label>
