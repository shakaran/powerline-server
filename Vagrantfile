# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

#id_rsa_ssh_key_pub = File.read(File.join(Dir.home, ".ssh", "id_rsa.pub"))

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "civix"
  config.vm.box_url = "https://cloud-images.ubuntu.com/vagrant/trusty/current/trusty-server-cloudimg-amd64-vagrant-disk1.box"
  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.network "private_network", ip: "192.168.10.100"
  config.vm.synced_folder ".", "/vagrant", type: "nfs"

  #config.vm.provision :shell, :inline => "echo 'Copying local id_rsa SSH Key to VM auth_keys for auth purposes (login into VM included)...' "
  #config.vm.provision :shell, :inline => "sudo mkdir -p /root/.ssh && sudo touch /root/.ssh/authorized_keys && sudo echo '#{id_rsa_ssh_key_pub }' > /root/.ssh/authorized_keys"

  config.vm.provision "ansible" do |ansible|
    ansible.inventory_path = "backend/deployment/ansible/inventory/all"
    ansible.playbook = "backend/deployment/ansible/vagrant.yml"
    ansible.sudo = true
  end

end
