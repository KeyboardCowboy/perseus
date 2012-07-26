<?php
/**
 * @file
 * Theme the system messages.
 */
?>
<div id="messages">
  <?php foreach ($messages as $type => $ms) : ?>
  <ul class="message-type message-type-<?php print $type; ?>">
    <?php foreach ($ms as $msg) : ?>
    <li><?php print $msg; ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endforeach; ?>
</div>
