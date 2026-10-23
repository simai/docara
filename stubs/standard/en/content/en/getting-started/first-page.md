---
description: "Add a page from a template, build the site and open the page in the browser."
profile: tutorial
---

# Create your first page

In this lesson you add a page to the site, build the site and open the page.
Replace this lesson with the first one your readers need.

## Before you start

- The project was created with `docara init`.
- A terminal is open in the project folder.

## Steps

1. Create a page from the how-to template:

   ```bash
   php vendor/bin/docara scaffold page how-to/my-task --type how_to --title "My task"
   ```

2. Open `content/en/how-to/my-task.md`. Replace every placeholder sentence
   and delete the line `draft: true`.
3. Build the site and check it:

   ```bash
   php vendor/bin/docara validate page /how-to/my-task/
   php vendor/bin/docara build production
   ```

4. Preview the site with `php vendor/bin/docara serve production --no-build`
   and open the page in the menu.

## Result

The page "My task" is in the "How-to guides" section of the menu.

## See also

- [Publish the site](/how-to/publish-the-site/)
- [Page types](/reference/page-types/)
