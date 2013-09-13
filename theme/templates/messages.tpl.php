<?php
/**
 * @file
 * Theme the system messages.
 */
?>
<div id="messages">
  <?php foreach ($messages as $type => $ms) : ?>
  <h2 class="hide"><?php print ucwords(\Perseus\System::errorCodes($type)) . " Messages"; ?></h2>
  <ul class="<?php print \Perseus\System::errorCodes($type); ?>">
    <?php foreach ($ms as $msg) : ?>
    <li><?php print $msg; ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endforeach; ?>
</div>
