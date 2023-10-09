<?php
/**
 * This template contains the output of a simple list of posts.
 *
 * @package Friends_Send_To_E_Reader
 */

?>
<html><head><title><?php echo esc_html( $args['title'] ); ?></title>
<script>
function selectAll() {
	var checkboxes = document.getElementsByTagName('input');
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].type == 'checkbox') {
			checkboxes[i].checked = true;
		}
	}
	return false;
}
function selectNone() {
	var checkboxes = document.getElementsByTagName('input');
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].type == 'checkbox') {
			checkboxes[i].checked = false;
		}
	}
	return false;
}
</script>
</head>
<body>
	<form>
		<span style="float: right"><a href="" onclick="return selectAll()">Select all</a> | <a href="" onclick="return selectNone()">Select none</a></span>
		<button>Download</button>
		<br />
		<?php foreach ( $args['posts'] as $post ) : ?>
				<input type="checkbox" name="<?php echo esc_attr( $args['inputname'] ); ?>[]" value="<?php echo esc_attr( $post->ID ); ?>" <?php checked( isset( $args['unsent'][ $post->ID ] ) ); ?> />
				<?php echo esc_html( get_the_title( $post ) ); ?>: <small><?php echo esc_html( get_the_excerpt( $post ) ); ?></small>
				<small><br><?php echo esc_html( get_the_permalink( $post ) ); ?><br><br></small>
		<?php endforeach; ?>
		<br/>
		<button>Download</button>
	</form>
	</body>
</html>
