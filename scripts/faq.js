const MarkdownIt = require('markdown-it')
const fs = require('fs');

var md = new MarkdownIt();
var content = "";

fs.readFile('./faq/faq.md', 'utf8', (err, data) => {
    if (err) {
      console.error(err);
      return;
    }
    content = md.render(data);

    fs.writeFile('./faq/faq.md.php', content, err => {
        if (err) {
            console.error(err);
        }
    });
});


