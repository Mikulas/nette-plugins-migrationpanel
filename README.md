<h1>MigrationPanel</h1>

Shows migrations that are under VCS and not listed in database. This means you won't be notified of migrations you are just committing. Migration date, author and subject are read from git.

Shows links to `/migrations` if the `migrations` directory is available over http.

<a>![Demo](http://i39.tinypic.com/jv6nw2.png)</a>

<dl>
	<dt>Requirements</dt>
	<dd>https://github.com/clevis/migration</dd>
	<dd>git versioning</dd>
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
