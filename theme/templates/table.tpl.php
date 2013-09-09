<?php
/**
 * @file
 * Theme a table.
 */
?>
<table <?php print \Perseus\System::htmlAttributes($attributes); ?>>
  <?php if (!empty($headers)) : ?>
  <thead>
    <?php foreach ($headers as $field => $header) : ?>
    <th><?php print $header; ?></th>
    <?php endforeach; ?>
  </thead>
  <?php endif; ?>

  <tbody>
    <?php foreach ($rows as $field => $row) : ?>
    <tr>
      <?php foreach ($row as $cell) : ?>
      <td><?php print $cell; ?></td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
