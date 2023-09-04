# Contributing

Thanks for being willing to contribute!

Is this **your first time** contributing to a different project? You might be interested in learning more about the workflow in [this free course](https://egghead.io/courses/how-to-contribute-to-an-open-source-project-on-github).

## Project setup

1. Fork and clone the repo
2. Run `npm install` to install all dependencies
4. Create a branch for your PR with `git checkout -b pr/your-branch-name`

If you want to build the project, run `npm run build`.

> Tip: Keep your `dev` branch pointing at the original repository and make
> pull requests from branches on your fork. To do this, run:
>
> ```
> git remote add upstream https://github.com/PhotoboothProject/photobooth.git
> git fetch upstream
> git branch --set-upstream-to=upstream/dev dev
> ```
>
> This will add the original repository as a "remote" called "upstream," Then
> fetch the git information from that remote, then set your local `dev`
> branch to use the upstream master branch whenever you run `git pull`. Then you
> can make all of your pull request branches based on this `dev` branch.
> Whenever you want to update your version of `dev`, do a regular `git pull`.

## Committing and pushing changes

Please make sure to run `npm run build` and `npm run eslint` before you commit your changes. Running `npm run eslint:fix` might be able to fix general issues on `*.js` files for you.  

**General Notes**  
- changes to the FAQ need to be done inside the `faq/faq.md`
- changes to the css-files need to be done inside [src/sass/](src/sass/) (Information can be found [here](resources/css/README.md))
- changes to the js-files need to be done inside [src/js/](src/js/) (Information can be found [here](resources/js/README.md))
- translation need to be done on [Crowdin](https://crowdin.com/project/photobooth)

## Help needed

Please checkout the [open issues](https://github.com/PhotoboothProject/photobooth/issues).

Also, please watch the repo and respond to questions / bug reports / feature requests.  
Thanks!
