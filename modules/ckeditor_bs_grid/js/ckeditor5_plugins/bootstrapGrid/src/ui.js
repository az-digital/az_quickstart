/**
 * @file registers the grid toolbar button and binds functionality to it.
 */
import { Plugin } from "ckeditor5/src/core";
import { ButtonView } from "ckeditor5/src/ui";
import icon from "../../../../icons/grid.svg";

export default class BootstrapGridUI extends Plugin {
  init() {
    const { editor } = this;
    const options = editor.config.get("bootstrapGrid");
    if (!options) {
      return;
    }

    const { dialogURL, openDialog, dialogSettings = {} } = options;

    if (!dialogURL || typeof openDialog !== "function") {
      return;
    }

    // This will register the grid toolbar button.
    editor.ui.componentFactory.add("bootstrapGrid", (locale) => {
      const command = editor.commands.get("insertBootstrapGrid");
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: editor.t("Bootstrap Grid"),
        icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      buttonView.bind("isOn", "isEnabled").to(command, "value", "isEnabled");
      this.listenTo(buttonView, "execute", () => {
        openDialog(
          dialogURL,
          ({ settings }) => {
            editor.execute("insertBootstrapGrid", settings);
          },
          dialogSettings
        );
      });

      return buttonView;
    });
  }
}
