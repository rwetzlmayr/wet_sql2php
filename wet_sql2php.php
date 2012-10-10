<?php
/* $LastChangedRevision: $ */
$plugin['version'] = '0.5';
$plugin['author'] = 'Robert Wetzlmayr';
$plugin['author_uri'] = 'http://wetzlmayr.com/';
$plugin['description'] = 'Export SQL as PHP source code';
$plugin['type'] = 3;

@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h3. Purpose

Exports the current template into PHP code for txpsql.php. Textpattern uses this for generating the default site during setup.

h3. Licence

This plugin is released under the "Gnu General Public Licence":http://www.gnu.org/licenses/gpl.txt.

# --- END PLUGIN HELP ---

<?php
}

# --- BEGIN PLUGIN CODE ---

class wet_sql2php
{
	static $template = '/setup/txpsql.php';
	static $what = array(
		'txp_css'   => array('name', 'css'),
		'txp_form'  => array('name', 'type', 'Form'),
		'txp_page'  => array('name', 'user_html'),
	);
	static $where = '$create_sql[]';

	/**
	 * Hook UI, setup privileges
	 */
	function __construct()
	{
		if (txpinterface == 'admin') {
			add_privs('wet_sql2php', '1');
			register_tab('presentation', 'wet_sql2php', gTxt('wet_sql2php'));
			register_callback(array(__CLASS__, 'ui'), 'wet_sql2php');
		}
	}

	/**
	 * User interface
	 */
	static function ui($event, $step)
	{
		global $step;

		pagetop(gTxt(__CLASS__));

		$available_steps = array(
			'export'
		);

		if (!$step or !in_array($step, $available_steps)){
			$step = 'export';
		}
		self::$step();
	}

	/**
	 * Weave the current template and show it ready to paste.
	 */
	static function export()
	{
		$f = file_get_contents(txpath.self::$template);

		foreach (self::$what as $table => $columns) {
			$tick = '`';
			$cols = (empty($columns) ? '*' : $tick.join('`,`', doSlash($columns)).$tick);
			$rs = safe_rows($cols, $table, '1=1' . (empty($columns) ? '' :  ' ORDER BY `'.$columns[0].'`'));

			$rows = array();
			foreach ($rs as $a) {
				// Enforce *nix new-lines
				$a = str_replace("\r\n", "\n", $a);
				// Literal string '\0' into corresponding MySQL literal
				$a = str_replace('\\'.'0', '\\'.'\\'.'\\'.'\\'.'0', $a);
				$a = "'" . join("', '", doSlash($a)) . "'";
				$rows[] = self::$where.' = "INSERT INTO `".PFX."'.$table.'`('.$cols.') VALUES('.$a.')";';
			}
			$f = preg_replace("#(// sql:$table).*(// /sql:$table)#s", '$1'.n. join(n, $rows) .n.'$2', $f);
		}

		echo text_area('code', 600, '', $f, 'code');
		echo script_js(<<<EOS
		$('#code').focus(function() {
			this.select();
		});
EOS
		);
	}
}

new wet_sql2php;

# --- END PLUGIN CODE ---

?>