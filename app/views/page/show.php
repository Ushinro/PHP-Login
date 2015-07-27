<?php include VIEW_ROOT . '/templates/header.php'; ?>

	<?php if (!$page): ?>
		<p>No page found, sorry.</p>
	<?php else: ?>
		<h2><?php echo e($page['title']); ?></h2>

		<?php echo e($page['body']); ?>

		<p class="faded">
			Created on <?php echo $page['created']->format('d M Y'); ?>
			<?php if ($page['updated']): ?>
				Last updated <?php echo $page['updated']->format('d M Y h:i a'); ?>
			<?php endif; ?>
		</p>
	<?php endif; ?>

<?php include VIEW_ROOT . '/templates/footer.php'; ?>