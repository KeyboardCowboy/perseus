<?php
/**
 * @file
 * Theme groups of radio buttons.
 */
?>
<?php foreach($options as $group => $radios) : ?>
  <div class="group <?php print $group; ?>">
    <?php foreach ($radios as $radio) : ?>
    <?php print $radio; ?>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
