# How to Contribute

## Prerequisites

- [GitHub account](https://github.com/signup/free)
- Familiarity with [GitHub PRs](https://help.github.com/articles/using-pull-requests) (pull requests) and issues
- Knowledge of Markdown for editing `.md` documents

## Getting Started

- Submit an [issue](https://github.com/cithukyaw/LucidFrame/issues), assuming one does not already exist.
  - Clearly describe the issue including steps to reproduce when it is a bug.
  - Make sure you fill in the earliest version that you know has the issue.
- Fork the repository on GitHub.


## Making and Submitting Changes

We follow [the successful Git branching model](http://nvie.com/posts/a-successful-git-branching-model/).

- Create a **topic/feature** branch from where you want to base your work. This is usually the **dev** branch: `$ git checkout -b myfeature dev`
- For bug fixes, create a branch from master `$ git checkout -b mybugfix master`.
- Only target release branches if you are certain your fix must be on that branch.
- Better avoid working directly on the master branch, to avoid conflicts if you pull in updates from origin.
- Make commits using descriptive commit messages and reference the #issue number (if any).
- Push your changes to a topic branch in your fork of the repository.
- Submit a pull request to [the LucidFrame original repository](github.com/cithukyaw/LucidFrame), with the correct target branch.

## Which branch?

- Bugfix branches will be based on master.
- New features that are backwards compatible will be based on next minor release branch.
- New features or other non backwards compatible changes will go in the next major release branch.

# Coding Standards

- [PSR-2](http://www.php-fig.org/psr/psr-2/) is in favour.
- Code MUST use 4 spaces for indenting, not tabs.
- A class opening `{` must be on the next line.
- A method or function opening `{` must be on the next line.
- Class names MUST be declared in [StudlyCaps](http://en.wikipedia.org/wiki/CamelCase), e.g., `Class FooBar { }`.
- Variable names
  - MUST be declared in [camelCase](http://en.wikipedia.org/wiki/CamelCase), e.g., `$fooBar`.
  - Some variable names could be prefixed like `$lc_fooBar` as for namespace.
- Function names
  - MUST be declared in [camelCase](http://en.wikipedia.org/wiki/CamelCase), e.g., `function fooBar() { }`
  - Utility helper function name MUST be declared preceding by an underscore, e.g., `function _fooBar() { }`.
  - Private/Internal function name MUST be declared preceding by a double underscore, e.g., `function __fooBar() { }`.
  - Some function names could be prefixed like `function foo_barHello() { }` as for namespace.
