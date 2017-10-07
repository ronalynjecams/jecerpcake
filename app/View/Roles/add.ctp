<div id="content-container"> 
    <div id="panel">
        <div class="roles form">

            <div class="row">
                <div class="col-md-12">
                    <div class="page-header">
                        <h1><?php echo __('Add Role'); ?></h1>
                    </div>
                </div>
            </div>



            <div class="row">
                <div class="col-md-3">
                    <div class="actions">
                        <div class="panel panel-default">
                            <div class="panel-heading">Actions</div>
                            <div class="panel-body">
                                <ul class="nav nav-pills nav-stacked">

                                    <li><?php echo $this->Html->link(__('<span class="glyphicon glyphicon-list"></span>&nbsp;&nbsp;List Roles'), array('action' => 'index'), array('escape' => false)); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>			
                </div><!-- end col md 3 -->
                <div class="col-md-9">
                    <?php echo $this->Form->create('Role', array('role' => 'form')); ?>

                    <div class="form-group">
                        <?php echo $this->Form->input('name', array('class' => 'form-control', 'placeholder' => 'Name')); ?>
                    </div>
                    <div class="form-group">
                        <?php echo $this->Form->submit(__('Submit'), array('class' => 'btn btn-default')); ?>
                    </div>

                    <?php echo $this->Form->end() ?>

                </div><!-- end col md 12 -->
            </div><!-- end row -->
        </div>
    </div>
</div>