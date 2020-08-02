# Contributing

Thanks for being willing to contribute!

Is this **your first time** contributing to a different project? You might be interested in learning more about the workflow in [this free course](https://egghead.io/courses/how-to-contribute-to-an-open-source-project-on-github).

## Project setup

1. Fork and clone the repo
2. To install all client dependencies you have to [install yarn](https://yarnpkg.com/lang/en/docs/install/#debian-stable):
```
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt update && sudo apt install -y yarn
``` 
3. Run `yarn install` to install all dependencies
4. Create a branch for your PR with `git checkout -b pr/your-branch-name`

If you want to build the project, run `yarn build`.

> Tip: Keep your `dev` branch pointing at the original repository and make
> pull requests from branches on your fork. To do this, run:
>
> ```
> git remote add upstream https://github.com/andi34/photobooth.git
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

Please make sure to run `yarn build` and `yarn eslint` before you commit your changes. Running `yarn eslint:fix` might be able to fix general issues on `*.js` files for you.  
If you're making changes to the FAQ (`faq/faq.md`) please run `yarn build:faq` before committing to generate needed HTML page.

## Help needed

Please checkout the [open issues](https://github.com/andi34/photobooth/issues).

Also, please watch the repo and respond to questions/bug reports/feature
requests. Thanks!
