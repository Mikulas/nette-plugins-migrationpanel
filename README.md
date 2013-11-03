<h1>MigrationPanel</h1>
<a>![Demo](http://i39.tinypic.com/jv6nw2.png)</a>

<dl>
	<dt>Requirements</dt>
	<dd>https://github.com/clevis/migration</dd>
</dl>

Example registration (`config.neon`):
```
nette:
	debugger:
		bar:
			- @migrationPanel

services:
	migrationPanel:
		class: Migration\NettePanel(%appDir%, @dibiConnection)
		setup:
			- addDirectory(%appDir%/../migrations/data)
			- addDirectory(%appDir%/../migrations/struct)
```

<h2>TODO</h2>
* package, composer
* caching
