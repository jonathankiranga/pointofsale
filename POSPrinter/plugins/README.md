# Custom printer adapters

Place trusted Node.js adapter modules in this directory and configure a printer profile with `"adapter": "plugin"` and `"module": "your-adapter.js"`.

Export a class constructor or `createAdapter(profile)`. The adapter must implement `print(buffer): Promise<{ delivery?: string }>` and may implement `openDrawer(buffer)`.

Plugins execute with the print agent's operating-system permissions. Only install modules you trust.
