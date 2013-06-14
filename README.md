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


*	`findInSet` - boolean - defaults to `false`

	The selection is converted to a comma separated list, when retrieving it.
	Already stored arrays are converted, when saved again.


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


### DCA example

```php
$GLOBALS['TL_DCA']['tl_mydca']['fields']['mySelectriField'] = array(
	...
	'inputType' => 'selectri',
	...
	'eval' => array(
		'min'			=> 0,
		'max'			=> 1,
		'mandatory'		=> null,
		'multiple'		=> null,
		'searchLimit'	=> 20,
		'findInSet'		=> false,
		'sort'			=> 'list',
		'height'		=> 'auto',
		'tl_class'		=> 'clr',
		'data'			=> 'SelectriContaoTableDataFactory',
		'treeTable'		=> 'tl_page',
		'mode'			=> 'all',
	),
	...
);
```
