<?php
/**
 * @package  mod_randomstrapline
 *
 * @copyright   Copyright (C) 2018 Simon Champion.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>
<div class="<?php echo $htmlclass; ?>">
<?php if ($params->get('show_item_title')) : ?>
	<h4 class="<?php echo $htmlclass; ?>-title">
	<?php if ($item->link !== '' && $params->get('link_title')) : ?>
		<a href="<?php echo $item->link; ?>">
			<?php echo $item->title; ?>
		</a>
	<?php else : ?>
		<?php echo $item->title; ?>
	<?php endif; ?>
	</h4>
<?php endif; ?>

<?php if (count($showBefore)): ?>
<div class="<?php echo $htmlclass; ?>-before">
<?php foreach($showBefore as $HTMLbefore): ?>
<?php echo $HTMLbefore; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($item->introtext): ?>
<div class="<?php echo $htmlclass; ?>-article">
<?php echo $item->introtext; ?>
</div>
<?php endif; ?>

<?php if ($params->get('show_fields') && count($fields)) : ?>
    <dl class="fields-container">
    <?php foreach ($fields as $field): ?>
        <?php if ($field->value && $field->params->show_on != 2): ?>
            <dd class="field-entry <?php echo $field->params->render_class; ?>">
            <?php if ($field->params->showlabel == 1) : ?>
                <span class="field-label"><?php echo htmlentities($field->label, ENT_QUOTES | ENT_IGNORE, 'UTF-8'); ?>: </span>
            <?php endif; ?>
            <span class="field-value"><?php echo $field->value; ?></span>
            </dd>
        <?php endif; ?>
    <?php endforeach; ?>
    </dl>
<?php endif; ?>

<?php if (count($showAfter)): ?>
<div class="<?php echo $htmlclass; ?>-after">
<?php foreach($showAfter as $HTMLafter): ?>
<?php echo $HTMLafter; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>

