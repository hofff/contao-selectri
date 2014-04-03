# backboneit_selectri

A selection widget for large structured option sets.

## Usage in DCA

Input type token: `selectri`

### eval Parameters

**Basic widget configuration**

*	`min` - integer, non-negative - defaults to `0`

	How many nodes must be selected at least.

	If the `max` parameter is less than the configured minimum, the `min`
parameter will be considered equal to the configured maximum.


*	`max` - integer, positive - defaults to `1`

	How many nodes can be selected at most.


*	`mandatory` - boolean - *optional* - **deprecated**: the `min` parameter
should be used

	If given and `true`, the `min` parameter will be set to `1`, if it is not
`> 0` already.
	If given and `false`, the `min` parameter will be set to `0`.


*	`multiple` - boolean - *optional* - **deprecated**: the `max` parameter
should be used
	
	If given and `true`, the `max` parameter will be set to `PHP_INT_MAX`, if it
is not `> 1` already.
	If given and `false`, the `max` parameter will be set to `1`.


*	`searchLimit` - integer, positive - defaults to `20`

	The max nodes retrieved from searches.


*	`additionalInput` - boolean - defaults to `false`

	If set to `true`, the values of this widget are maps from selected nodes'
	keys to arrays containing additional (input) data. This allows the injection
	of HTML inputs unrelated to the selection itself into the nodes.
	For ease of use, each node's key is additionally stored in the data array at
	the array key `_key`.


*	`findInSet` - boolean - defaults to `false`

	The selection is converted to a comma separated list, when retrieving it.
	Already stored arrays are converted, when saved again. This overrides the
	behavior of the `additionalInput` setting. However the unconverted widget
	value can be retrieved via the widget's `originValue` property.


*	`sort` - string, one of `list`, `preorder`, `tree` - defaults to `list`

	The structure implied on the made selection
	
	- `list`: the selection is a list (order matters), the selection is
sortable
	- `preorder` ***not implemented***: the selection is a set (order does not
matter), the selection is not sortable and preordered
	- `tree` ***not implemented***: the selection is a tree, the selection can
be arranged with tree properties


*	`height` - string - defaults to `auto`

	The CSS value and unit of the CSS `height` property of the selection tree.


*	`tl_class` - string - *optional*

	Only used in Backend.


*	`class` - string - *optional*

	Use this to apply your custom CSS savely. You can use "radio" or "checkbox"
to replace the Contao-style select (+) and deselect (x) icons with Windows
legacy input-like icon images.


*	`data` - string or an object implementing `SelectriDataFactory` - defaults
to the string `SelectriContaoTableDataFactory`

	If a factory **object** is given, these factory will be used to generate
a data instance for created widgets. The `setParameters` method is **not**
called. This is the recommended way to provide a `SelectriData`, as all data
implementation specific features and options can be properly configured through
their factories.
	
	If a string is given, it must name a class implementing
`SelectriDataFactory`. When creating the widget, a new factory instance is
created and its `setParameters` method is called with the attributes of the
widget (the widget's attributes contains all settings from the DCA's `eval`
array).


**Factory specific configuration (used by `setParameters` method of the
`SelectriDataFactory` class given in the `data` parameter)**

*	`treeTable` - string - *optional*
	
	The name of the table to fetch tree nodes from.
	
	The parameter is used by `SelectriContaoTableDataFactory::setParameters`
method and preconfigures the data according to common Contao standards like
primary key column `id` and parent key column `pid`.

*	`mode` - string - defaults to `all`

	The parameter is used by `SelectriTableDataFactory::setParameters`
method.

	- `all`: all nodes are selectable
	- `leaf`: only leaf nodes are selectable
	- `inner`: only inner nodes are selectable


### Simple example for usage in DCA

```php
$GLOBALS['TL_DCA']['tl_mydca']['fields']['mySelectriField'] = array(
	...
	'inputType' => 'selectri',
	...
	'eval' => array(
    	// all values are the defaults
		'min'				=> 0,			// the selection can be empty
		'max'				=> 1,			// let the user select not more than 1 item
		'searchLimit'		=> 20,			// max search results
		'findInSet'			=> false,		// dont use csv
		'additionalInput'	=> false,		// no additional inputs via node content callback is injected
		'sort'				=> 'list',
		'height'			=> 'auto',		// the height of the tree widget
		'tl_class'			=> 'clr',		// some css-classes,
		'class'				=> '',			// use "radio" or "checkbox" to replace the icons
		'data'				=> 'SelectriContaoTableDataFactory', // the data factory class to use
		'treeTable'			=> 'tl_page',	// a DB-table containing the tree structure (Contao-like adjacency list)
		'mode'				=> 'all',		// which nodes are selectable: "all", "leaf", "inner"
	),
	...
);
```

### Advanced example for usage in DCA

Instead of using a implicit created factory instance by providing a factory
class name in the previous example, you can preconfigure your own factory
instance and have full access to all parameters used by the `SelectriData`-class
produced by the factory.

```php
$data = SelectriContaoTableDataFactory::create();

// use the tl_page table for the tree structure
$data->setTreeTable('tl_page');

// show all nodes
$data->getConfig()->setTreeMode('all');

// search the title and pageTitle column
$data->getConfig()->setTreeSearchColumns(array('title', 'pageTitle'));

// only show nodes matching the condition
$data->getConfig()->setTreeConditionExpr('type = \'regular\' AND tstamp > 0');

// only let the user select nodes matching the condition
$data->getConfig()->setSelectableExpr('hide <> \'1\'');

// for more parameters see the factory class and the underlaying config class

$GLOBALS['TL_DCA']['tl_mydca']['fields']['mySelectriField'] = array(
	...
	'inputType' => 'selectri',
	...
	'eval' => array(
		'min'			=> 0,
		'max'			=> 1,
		'searchLimit'	=> 20,
		'tl_class'		=> 'clr',
		'class'			=> 'checkbox',
		
		// assign your preconfigured factory instance to the widgets configuration
		'data'			=> $data,
	),
	...
);
```
