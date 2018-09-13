easyengine/scaffold-command
===========================





Quick links: [Using](#using) | [Contributing](#contributing) | [Support](#support)

## Using

~~~
ee scaffold package-readme <dir> [--force]
~~~

Creates a README.md with Using, Installing, and Contributing instructions
based on the composer.json file for your EE package. Run this command
at the beginning of your project, and then every time your usage docs
change.

These command-specific docs are generated based composer.json -> 'extra'
-> 'commands'. For instance, this package's composer.json includes:

```
{
  "name": "easyengine/scaffold-command",
   // [...]
   "extra": {
       "commands": [
           "scaffold package-readme"
       ]
   }
}
```

**OPTIONS**

	<dir>
		Directory path to an existing package to generate a readme for.

	[--force]
		Overwrite the readme if it already exists.

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.


### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/easyengine/scaffold-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/easyengine/scaffold-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible.

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/easyengine/scaffold-command/issues/new) to discuss whether the feature is a good fit for the project.

## Support

Github issues aren't for general support questions, but there are other venues you can try: https://easyengine.io/support/


*This README.md is generated dynamically from the project's codebase using `ee scaffold package-readme` ([doc](https://github.com/easyengine/scaffold-package-command)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
