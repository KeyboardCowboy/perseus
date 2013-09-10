<?php
/**
 * @file
 * Theme a table.
 */
use \Perseus\System;
?>
<table <?php print System::htmlAttributes($attributes); ?>>
  <?php if (!empty($headers)) : ?>
  <thead>
    <?php foreach ($headers as $field => $header) : ?>
    <th><?php print $header; ?></th>
    <?php endforeach; ?>
  </thead>
  <?php endif; ?>

  <?php if (empty($rows)) : ?>

  <tbody>
    <tr>
      <td colspan="<?php print count($headers); ?>"><?php print $empty; ?></td>
    </tr>
  </tbody>

  <?php else : ?>

  <tbody>
    <?php foreach ($rows as $field => $row) : ?>
    <tr>
      <?php foreach ($row as $cell) : ?>
      <td><?php print $cell; ?></td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>

  <?php endif; ?>
</table>
