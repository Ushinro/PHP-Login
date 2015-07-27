<aside id="flash">
<?php if (Session::exists('success')) : ?>
	<div id="success">
		<?php echo Session::flash('success'); ?>
	</div>
<?php endif;
if (Session::exists('error')) : ?>
	<div id="error">
		<?php echo Session::flash('error'); ?>
	</div>
<?php endif; ?>
</aside>