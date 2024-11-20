# Studio User
## Default Key Bindings
To change the default key bindings, you can add a synfony configuration file in your project. 
The structure of the file should be like this:
```yaml
pimcore_studio_backend:
  user:
    default_key_bindings:
      save:
        key: 'S'
        action: save
        ctrl: true
```

You can find the predefined key bindings here `config/pimcore/user_key_binding.yaml` in the Studio Backend Bundle.
