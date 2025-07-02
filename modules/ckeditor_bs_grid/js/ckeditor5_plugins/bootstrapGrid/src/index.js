import { Plugin } from "ckeditor5/src/core";
import BootstrapGridEditing from "./editing";
import BootstrapGridUi from "./ui";
import BootstrapGridToolbar from "./toolbar";

class BootstrapGrid extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [BootstrapGridEditing, BootstrapGridUi, BootstrapGridToolbar];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return "BootstrapGrid";
  }
}

export default {
  BootstrapGrid,
};
