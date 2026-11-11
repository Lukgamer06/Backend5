targetScope = 'subscription'

@minLength(5)
@maxLength(90)
param resourceGroupName string = 'rg-hostalconnect-unab-20261111-cus'

@minLength(1)
@maxLength(64)
param environmentName string = 'hostalconnect-demo'

param location string = 'centralus'

param adminUsername string = 'hcadmin'

@secure()
param sshPublicKey string

param sshSourceAddressPrefix string

var tags = {
  application: 'HostalConnect UNAB'
  environment: environmentName
  managedBy: 'Bicep'
}

resource resourceGroup 'Microsoft.Resources/resourceGroups@2023-07-01' = {
  name: resourceGroupName
  location: location
  tags: tags
}

module vm './modules/vm.bicep' = {
  name: 'hostalconnect-vm'
  scope: resourceGroup
  params: {
    adminUsername: adminUsername
    environmentName: environmentName
    location: location
    sshPublicKey: sshPublicKey
    sshSourceAddressPrefix: sshSourceAddressPrefix
    tags: tags
  }
}

output resourceGroupName string = resourceGroup.name
output vmName string = vm.outputs.vmName
output publicIpAddress string = vm.outputs.publicIpAddress
output fqdn string = vm.outputs.fqdn
