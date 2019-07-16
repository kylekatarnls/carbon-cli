# carbon-cli

CLI tool for Carbon

## Install

```shell
composer require carbon-cli/carbon-cli
```

Note that if you have `nesbot/carbon` installed yet and try to run a command, Carbon will automatically try to
install the CLI using global composer command.

## Usage

### macro

Generate macro helpers files for your IDE.

```shell
./vendor/bin/carbon macro NameSpace1\\Class1 NameSpace2\\Class2 src/macro-file.php
```

You can pass classes and files to the `macro` commands, classes will be loaded into Carbon as mixin, files will be
loaded via `include` so you can run `Carbon::macro()` inside.

It will create **_ide_carbon_mixin_instantiated.php** and **_ide_carbon_mixin_macro.php** with all mixin/macro
methods signatures, so your IDE will be able to auto-complete them on Carbon facade and instances.

You can commit those files into your project. And you should re-run the command when adding a new mixin/macro.

You also can store the list in your **composer.json**:

```json
{
  "extra": {
    "carbon": {
      "macros": [
        "NameSpace1\\Class1",
        "NameSpace2\\Class2",
        "src/macro-file.php"
      ]
    }
  }
}
```

Then run:

```shell
./vendor/bin/carbon macro --composer
```

By default, the command will only consider the current directory (app, sources, tests, vendor, etc.) and so will
also include the composer settings of your installed vendor packages.

To restrict to a given directory, use:

```shell
./vendor/bin/carbon macro --source-path app/Carbon
```

This will consider only mixin/macro declared inside **app/Carbon** directory.

This option can be used either with `--composer` option, with arguments list or both.
