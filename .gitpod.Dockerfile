FROM gitpod/workspace-full
RUN curl -o olderrunc -L https://github.com/opencontainers/runc/releases/download/v1.0.0-rc93/runc.amd64 && chmod 755 olderrunc
RUN sudo rm /usr/bin/runc && sudo cp olderrunc /usr/bin/runc
RUN sudo apt-get -qq update && sudo apt-get install -y zsh && sudo chsh -s $(which zsh)
RUN sh -c "$(curl -fsSL https://starship.rs/install.sh)" -- --yes && echo 'eval "$(starship init zsh)"' > .zshrc
RUN curl -OL https://github.com/lando/lando/releases/download/v3.6.1/lando-x64-v3.6.1.deb && sudo dpkg -i --ignore-depends docker-ce,iptables lando-x64-v3.6.1.deb && rm -rf lando-x64-v3.6.1.deb
RUN mkdir -p ~/.lando && echo "proxy: 'ON'\nproxyHttpPort: '8080'\nproxyHttpsPort: '4443'\nbindAddress: '0.0.0.0'\nproxyBindAddress: '0.0.0.0'" > ~/.lando/config.yml
