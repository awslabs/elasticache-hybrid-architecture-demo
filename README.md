# Reduce latency of hybrid architectures with Amazon ElastiCache

Demo code to deploy and test Amazon ElastiCache in hybrid architectures with database living on-premises or in AWS.

## Launch of AWS CloudFormation template

To deploy the template and test the demo code:

1. Log into AWS Console
2. Go to AWS Cloudformation's console and click on "Create Stack"
3. Choose "Specify an Amazon S3 template URL" with the next URL and click Next

   **https://github.com/awslabs/elasticache-hybrid-architecture-demo/cloudformation-template.yaml**

4. Give a name to your Stack like "ElastiCacheDemo", fill the Parameters and click Next, the most important parameters are:

   ** DatabaseServer ** It is your database address, IP or hostname
   ** DatabaseName ** Name of your database or schema
   ** DatabaseUsername ** Your database's username
   ** KeyName ** Your key pair to SSH access the instance
   ** Vpc ** VPC where your Amazon EC2 instance will be deployed
   ** Subnet ** Subnet where your Amazon EC2 instance will be deployed

5. Select the Tags to include in your Stack and click Next

6. Review your options, select the "I acknowledge that AWS CloudFormation might create IAM resources." and click on "Create"

7. Once the Stack is ready, go to "Outputs", copy the "DemoScript" URL and open it in your web browser

8. Test your database response times with and without caching !!
